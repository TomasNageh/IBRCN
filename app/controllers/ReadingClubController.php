<?php

/**
 * FILE: ReadingClubController.php
 * PURPOSE: Coordinates the Reading Clubs page workflows (create/join/leave/current-read/discussions) and prepares view data.
 * USED BY: `public/member.php` endpoint, which renders `app/views/reader/member.php`.
 * DESIGN PATTERN: None — controller calls models/services; SQL remains inside `ReadingClub`/`User` models.
 */

// Handles request/response coordination for the Reading Clubs module without embedding SQL or HTML (controller layer).
class ReadingClubController
{
    /**
     * @param PDO        $db              Shared PDO from `DB::getInstance()`.
     * @param ReadingClub $readingClubModel Reading-club model handling all club SQL.
     * @param User       $userModel        User model used to resolve reader accounts for invites.
     * @return void
     */
    public function __construct(
        private PDO $db,
        private ReadingClub $readingClubModel,
        private User $userModel
    ) {
    }

    /**
     * Handles the full request lifecycle for `public/member.php` and returns the view variables.
     *
     * @return array<string,mixed> Variables consumed by `app/views/reader/member.php`.
     */
    public function handleRequest(): array
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }

        $this->readingClubModel->ensureSchema();

        $sessionUserId = isset($_SESSION['user_id']) ? (int) $_SESSION['user_id'] : 0;
        $sessionUserRow = $sessionUserId ? $this->userModel->findById($sessionUserId) : null;
        $isReader = $sessionUserRow && (string) $sessionUserRow['role'] === 'Reader';

        $editThreadId = isset($_GET['edit_thread']) ? (int) $_GET['edit_thread'] : 0;
        $editThreadId = $this->sanitizeEditThreadId($editThreadId, $isReader, $sessionUserRow, $sessionUserId);

        $inviteUsers = $isReader ? $this->readingClubModel->listReadersForInvite($sessionUserId) : array();

        $myClubs = $sessionUserRow
            ? $this->readingClubModel->findClubsForUser($sessionUserId, (string) $sessionUserRow['email'])
            : array();

        $myClubIds = array_map(static function ($row) {
            return (int) $row['club_id'];
        }, $myClubs);

        $message = '';

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $message = $this->handlePost($isReader, $sessionUserRow, $sessionUserId, $myClubs, $myClubIds);
        }

        $clubs = $this->readingClubModel->listAllClubsNewestFirst();

        $memberReadsByClub = array();
        $threadsByClub = array();
        if ($sessionUserRow && !empty($myClubs)) {
            $clubIds = array_column($myClubs, 'club_id');
            $memberReadsByClub = $this->readingClubModel->getMemberReadsForClubs($clubIds);
            $threadsByClub = $this->readingClubModel->getDiscussionThreadsForClubs($clubIds);
        }

        return array(
            'db' => $this->db,
            'readingClub' => $this->readingClubModel,
            'userModel' => $this->userModel,
            'sessionUserId' => $sessionUserId,
            'sessionUserRow' => $sessionUserRow,
            'isReader' => $isReader,
            'editThreadId' => $editThreadId,
            'inviteUsers' => $inviteUsers,
            'myClubs' => $myClubs,
            'myClubIds' => $myClubIds,
            'message' => $message,
            'clubs' => $clubs,
            'memberReadsByClub' => $memberReadsByClub,
            'threadsByClub' => $threadsByClub,
        );
    }

    /**
     * Ensures the `edit_thread` query param is valid and belongs to the current reader.
     *
     * @param int        $editThreadId   Raw thread id from GET.
     * @param bool       $isReader       Whether the signed-in user is a Reader.
     * @param array|null $sessionUserRow User row or null.
     * @param int        $sessionUserId  Signed-in user id (0 when guest).
     * @return int Sanitized thread id (0 when invalid).
     */
    private function sanitizeEditThreadId(int $editThreadId, bool $isReader, ?array $sessionUserRow, int $sessionUserId): int
    {
        if ($editThreadId <= 0 || !$isReader || !$sessionUserRow) {
            return 0;
        }

        $__et = $this->readingClubModel->getDiscussionThreadById($editThreadId);
        if (
            !$__et
            || (int) $__et['user_id'] !== $sessionUserId
            || !$this->readingClubModel->userIsMemberOfClub((int) $__et['club_id'], $sessionUserId, (string) $sessionUserRow['email'])
        ) {
            return 0;
        }

        return $editThreadId;
    }

    /**
     * Executes POST actions and returns the message string for the page.
     *
     * @param bool       $isReader       Whether the signed-in user is a Reader.
     * @param array|null $sessionUserRow User row or null.
     * @param int        $sessionUserId  Signed-in user id.
     * @param array      $myClubs        Current member clubs (will be refreshed after changes).
     * @param array      $myClubIds      Current member club ids (will be refreshed after changes).
     * @return string Result message to display (empty string allowed).
     */
    private function handlePost(bool $isReader, ?array &$sessionUserRow, int $sessionUserId, array &$myClubs, array &$myClubIds): string
    {
        $message = '';
        $action = isset($_POST['action']) ? (string) $_POST['action'] : '';

        if ($action === 'create_club') {
            if ($sessionUserRow && !$isReader) {
                return 'Reading clubs are only for accounts with the Reader role.';
            }

            $name = trim((string) ($_POST['club_name'] ?? ''));
            $desc = trim((string) ($_POST['club_description'] ?? ''));
            $postEmail = trim((string) ($_POST['email'] ?? ''));

            if ($name === '') {
                return 'Club name is required.';
            }

            $creatorEmail = $sessionUserRow ? (string) $sessionUserRow['email'] : $postEmail;
            $creatorUserId = $sessionUserRow ? $sessionUserId : null;

            $clubId = $this->readingClubModel->createClub(
                $name,
                $desc !== '' ? $desc : null,
                $creatorEmail !== '' ? $creatorEmail : null,
                $creatorUserId
            );

            if ($sessionUserRow) {
                $this->readingClubModel->addMember($clubId, $sessionUserId, (string) $sessionUserRow['email']);
            } elseif ($creatorEmail !== '' && filter_var($creatorEmail, FILTER_VALIDATE_EMAIL)) {
                $this->readingClubModel->addMemberByEmailOnly($clubId, $creatorEmail);
            }

            if ($isReader && !empty($_POST['invite_user_ids']) && is_array($_POST['invite_user_ids'])) {
                foreach ($_POST['invite_user_ids'] as $rawId) {
                    $otherUserId = (int) $rawId;
                    if ($otherUserId <= 0 || $otherUserId === $sessionUserId) {
                        continue;
                    }
                    $invitedUser = $this->userModel->findById($otherUserId);
                    if ($invitedUser && (string) $invitedUser['role'] === 'Reader') {
                        $this->readingClubModel->addMember($clubId, $otherUserId, (string) $invitedUser['email']);
                    }
                }
            }

            $message = 'Club created successfully.';
        }

        if ($action === 'join_club') {
            $clubId = (int) ($_POST['club_id'] ?? 0);
            if ($sessionUserRow) {
                if (!$isReader) {
                    $message = 'Only reader accounts can join reading clubs.';
                } elseif ($this->readingClubModel->addMember($clubId, $sessionUserId, (string) $sessionUserRow['email'])) {
                    $message = 'Joined the club successfully.';
                } else {
                    $message = 'You are already a member of this club.';
                }
            } else {
                $message = 'Please sign in with a reader account to join a club.';
            }
        }

        if ($action === 'leave_club' && $isReader && $sessionUserRow) {
            $clubId = (int) ($_POST['club_id'] ?? 0);
            if ($clubId > 0) {
                if ($this->readingClubModel->leaveClub($clubId, $sessionUserId, (string) $sessionUserRow['email'])) {
                    $message = 'You have left the club.';
                } else {
                    $message = 'Could not leave this club (membership not found).';
                }
            }
        }

        if ($action === 'save_current_read' && $isReader && $sessionUserRow) {
            $clubId = (int) ($_POST['club_id'] ?? 0);
            $title = trim((string) ($_POST['book_title'] ?? ''));
            $author = trim((string) ($_POST['book_author'] ?? ''));

            if ($clubId > 0 && $this->readingClubModel->userIsMemberOfClub($clubId, $sessionUserId, (string) $sessionUserRow['email'])) {
                $this->readingClubModel->saveMyCurrentRead($clubId, $sessionUserId, $title, $author !== '' ? $author : null);
                $message = $title !== '' ? 'Your current read was saved.' : 'Your current read was cleared.';
            }
        }

        if ($action === 'discussion_create' && $isReader && $sessionUserRow) {
            $clubId = (int) ($_POST['club_id'] ?? 0);
            $dTitle = trim((string) ($_POST['disc_title'] ?? ''));
            $dBody = trim((string) ($_POST['disc_body'] ?? ''));

            if (
                $clubId > 0
                && $this->readingClubModel->userIsMemberOfClub($clubId, $sessionUserId, (string) $sessionUserRow['email'])
            ) {
                if ($this->readingClubModel->addDiscussionThread($clubId, $sessionUserId, $dTitle, $dBody)) {
                    header('Location: member.php#discussions-c' . $clubId);
                    exit;
                }
                $message = 'Discussion title and message are required.';
            }
        }

        if ($action === 'discussion_update' && $isReader && $sessionUserRow) {
            $threadId = (int) ($_POST['thread_id'] ?? 0);
            $dTitle = trim((string) ($_POST['disc_title'] ?? ''));
            $dBody = trim((string) ($_POST['disc_body'] ?? ''));
            $threadRow = $threadId > 0 ? $this->readingClubModel->getDiscussionThreadById($threadId) : null;

            if (
                $threadRow
                && (int) $threadRow['user_id'] === $sessionUserId
                && $this->readingClubModel->userIsMemberOfClub((int) $threadRow['club_id'], $sessionUserId, (string) $sessionUserRow['email'])
            ) {
                if ($this->readingClubModel->updateDiscussionThread($threadId, $sessionUserId, $dTitle, $dBody)) {
                    header('Location: member.php#discussions-c' . (int) $threadRow['club_id']);
                    exit;
                }
                $message = 'Title and message cannot be empty.';
            }
        }

        if ($action === 'discussion_delete' && $isReader && $sessionUserRow) {
            $threadId = (int) ($_POST['thread_id'] ?? 0);
            $threadRow = $threadId > 0 ? $this->readingClubModel->getDiscussionThreadById($threadId) : null;
            if (
                $threadRow
                && (int) $threadRow['user_id'] === $sessionUserId
                && $this->readingClubModel->userIsMemberOfClub((int) $threadRow['club_id'], $sessionUserId, (string) $sessionUserRow['email'])
            ) {
                if ($this->readingClubModel->deleteDiscussionThread($threadId, $sessionUserId)) {
                    header('Location: member.php#discussions-c' . (int) $threadRow['club_id']);
                    exit;
                }
            }
        }

        // Refresh membership-derived lists after any action that might have changed membership/current-read.
        if ($sessionUserRow) {
            $myClubs = $this->readingClubModel->findClubsForUser($sessionUserId, (string) $sessionUserRow['email']);
            $myClubIds = array_map(static function ($row) {
                return (int) $row['club_id'];
            }, $myClubs);
        }

        return $message;
    }
}


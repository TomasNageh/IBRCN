<?php

/**
 * FILE: ReadingClub.php
 * PURPOSE: Reading-club schema bootstrap (CREATE/ALTER guards), membership, current-read tracking, and discussion thread CRUD.
 * USED BY: `public/member.php`, `ReaderClubDashboardService`, reader home club widgets.
 * DESIGN PATTERN: None — encapsulates club-related SQL while keeping strings identical to legacy queries.
 */

// Coordinates club tables (`reading_clubs`, `club_members`, `club_member_current_read`, `club_discussion_threads`) via PDO (model).
class ReadingClub
{
    /**
     * @param PDO $db Shared PDO (typically `DB::getInstance()`) for all club queries in this request.
     * @return void
     */
    public function __construct(private PDO $db)
    {
    }

    /**
     * Ensures required club tables/columns exist using idempotent CREATE TABLE and guarded ALTER statements.
     *
     * @return void
     */
    public function ensureSchema(): void
    {
        $this->db->exec(
            "CREATE TABLE IF NOT EXISTS reading_clubs (
                club_id INT AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(255) NOT NULL,
                description TEXT DEFAULT NULL,
                creator_email VARCHAR(255) DEFAULT NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
        );

        $this->db->exec(
            "CREATE TABLE IF NOT EXISTS club_members (
                member_id INT AUTO_INCREMENT PRIMARY KEY,
                club_id INT NOT NULL,
                email VARCHAR(255) NOT NULL,
                joined_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (club_id) REFERENCES reading_clubs(club_id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
        );

        $this->addColumnUnlessExists('reading_clubs', 'creator_user_id', 'INT UNSIGNED NULL DEFAULT NULL AFTER creator_email');
        $this->addColumnUnlessExists('club_members', 'user_id', 'INT UNSIGNED NULL DEFAULT NULL AFTER club_id');

        $this->db->exec(
            "CREATE TABLE IF NOT EXISTS club_member_current_read (
                read_id INT AUTO_INCREMENT PRIMARY KEY,
                club_id INT NOT NULL,
                user_id INT UNSIGNED NOT NULL,
                book_title VARCHAR(500) NOT NULL,
                book_author VARCHAR(255) DEFAULT NULL,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                UNIQUE KEY uniq_club_member_read (club_id, user_id),
                FOREIGN KEY (club_id) REFERENCES reading_clubs(club_id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
        );

        $this->db->exec(
            "CREATE TABLE IF NOT EXISTS club_discussion_threads (
                thread_id INT AUTO_INCREMENT PRIMARY KEY,
                club_id INT NOT NULL,
                user_id INT UNSIGNED NOT NULL,
                title VARCHAR(500) NOT NULL,
                body TEXT NOT NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX idx_disc_club_time (club_id, created_at),
                FOREIGN KEY (club_id) REFERENCES reading_clubs(club_id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
        );
    }

    /**
     * Adds a column to `$table` when missing (compat helper for incremental schema evolution on shared hosts).
     *
     * @param string $table      Physical table name validated by caller (trusted internal strings only).
     * @param string $column     Column name to ensure exists.
     * @param string $definition Column DDL fragment appended after ADD COLUMN (ENGINE-specific).
     * @return void
     */
    private function addColumnUnlessExists(string $table, string $column, string $definition): void
    {
        $stmt = $this->db->prepare(
            'SELECT COUNT(*) FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = :t AND COLUMN_NAME = :c'
        );
        $stmt->execute(array('t' => $table, 'c' => $column));
        if ((int) $stmt->fetchColumn() === 0) {
            $this->db->exec("ALTER TABLE `$table` ADD COLUMN `$column` $definition");
        }
    }

    /**
     * Returns clubs where the user is a member by `user_id` match or legacy email-only membership rows.
     *
     * @param int         $userId Signed-in user id when available.
     * @param string|null $email  Fallback email match for legacy rows where `club_members.user_id` may be NULL.
     * @return list<array<string,mixed>> Club rows including optional current-read fields for the requesting user.
     */
    public function findClubsForUser(int $userId, ?string $email): array
    {
        $em = ($email !== null && $email !== '') ? $email : '';
        $params = array(
            'uid' => $userId,
            'rid' => $userId,
            'em_a' => $em,
            'em_b' => $em,
        );
        $sql = 'SELECT DISTINCT rc.club_id, rc.name, rc.description, rc.creator_email, rc.created_at,
                       cr.book_title AS my_book_title, cr.book_author AS my_book_author
                FROM reading_clubs rc
                INNER JOIN club_members cm ON cm.club_id = rc.club_id
                LEFT JOIN club_member_current_read cr ON cr.club_id = rc.club_id AND cr.user_id = :rid
                WHERE cm.user_id = :uid
                   OR (TRIM(LOWER(cm.email)) = TRIM(LOWER(:em_a)) AND LENGTH(TRIM(:em_b)) > 0)
                ORDER BY rc.created_at DESC';

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: array();
    }

    /**
     * All members of each club and their saved current read (visible to fellow members).
     *
     * @param list<int> $clubIds
     * @return array<int, list<array{display_name:string,member_user_id:int|null,book_title:?string,book_author:?string}>>
     */
    public function getMemberReadsForClubs(array $clubIds): array
    {
        $clubIds = array_values(array_unique(array_map('intval', array_filter($clubIds))));
        if ($clubIds === array()) {
            return array();
        }

        $placeholders = implode(',', array_fill(0, count($clubIds), '?'));
        $sql = "SELECT cm.club_id,
                       cm.user_id AS member_user_id,
                       COALESCE(u.username, cm.email) AS display_name,
                       cr.book_title,
                       cr.book_author
                FROM club_members cm
                LEFT JOIN users u ON u.user_id = cm.user_id
                LEFT JOIN club_member_current_read cr ON cr.club_id = cm.club_id
                    AND cm.user_id IS NOT NULL
                    AND cr.user_id = cm.user_id
                WHERE cm.club_id IN ($placeholders)
                ORDER BY cm.club_id, display_name";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($clubIds);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: array();

        $out = array();
        foreach ($rows as $r) {
            $cid = (int) $r['club_id'];
            if (!isset($out[$cid])) {
                $out[$cid] = array();
            }
            $mid = $r['member_user_id'];
            $out[$cid][] = array(
                'member_user_id' => $mid !== null && $mid !== '' ? (int) $mid : null,
                'display_name' => (string) $r['display_name'],
                'book_title' => isset($r['book_title']) && $r['book_title'] !== '' ? (string) $r['book_title'] : null,
                'book_author' => isset($r['book_author']) && $r['book_author'] !== '' ? (string) $r['book_author'] : null,
            );
        }

        return $out;
    }

    /**
     * Discussion threads per club (newest first). Scoped to club_id — only load for clubs the user belongs to.
     *
     * @param list<int> $clubIds
     * @return array<int, list<array<string,mixed>>>
     */
    public function getDiscussionThreadsForClubs(array $clubIds): array
    {
        $clubIds = array_values(array_unique(array_map('intval', array_filter($clubIds))));
        if ($clubIds === array()) {
            return array();
        }

        $placeholders = implode(',', array_fill(0, count($clubIds), '?'));
        $sql = "SELECT t.thread_id, t.club_id, t.user_id, t.title, t.body, t.created_at, t.updated_at,
                       COALESCE(u.username, CONCAT('User #', t.user_id)) AS author_name
                FROM club_discussion_threads t
                INNER JOIN users u ON u.user_id = t.user_id
                WHERE t.club_id IN ($placeholders)
                ORDER BY t.club_id, t.created_at DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($clubIds);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: array();

        $out = array();
        foreach ($rows as $r) {
            $cid = (int) $r['club_id'];
            if (!isset($out[$cid])) {
                $out[$cid] = array();
            }
            $out[$cid][] = array(
                'thread_id' => (int) $r['thread_id'],
                'club_id' => (int) $r['club_id'],
                'user_id' => (int) $r['user_id'],
                'title' => (string) $r['title'],
                'body' => (string) $r['body'],
                'created_at' => (string) $r['created_at'],
                'updated_at' => (string) $r['updated_at'],
                'author_name' => (string) $r['author_name'],
            );
        }

        return $out;
    }

    /**
     * Loads a discussion thread header row by primary key (does not prove caller membership — caller must enforce).
     *
     * @param int $threadId `club_discussion_threads.thread_id`.
     * @return array<string,mixed>|null Thread row or null if missing.
     */
    public function getDiscussionThreadById(int $threadId): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT thread_id, club_id, user_id, title, body, created_at, updated_at
             FROM club_discussion_threads WHERE thread_id = :id LIMIT 1'
        );
        $stmt->execute(array('id' => $threadId));
        $r = $stmt->fetch(PDO::FETCH_ASSOC);

        return $r ?: null;
    }

    /**
     * Inserts a new discussion thread authored by `userId` in `clubId` after trimming title/body.
     *
     * @param int    $clubId  Club scope foreign key.
     * @param int    $userId  Author user id.
     * @param string $title   Thread title (non-empty after trim).
     * @param string $body    Thread body (non-empty after trim).
     * @return int|null New `thread_id`, or null when validation fails / insert fails.
     */
    public function addDiscussionThread(int $clubId, int $userId, string $title, string $body): ?int
    {
        $title = trim($title);
        $body = trim($body);
        if ($title === '' || $body === '') {
            return null;
        }

        $stmt = $this->db->prepare(
            'INSERT INTO club_discussion_threads (club_id, user_id, title, body) VALUES (:c, :u, :t, :b)'
        );
        if (!$stmt->execute(array('c' => $clubId, 'u' => $userId, 't' => $title, 'b' => $body))) {
            return null;
        }

        return (int) $this->db->lastInsertId();
    }

    /**
     * Updates thread title/body only when the author user matches (AND clause in SQL).
     *
     * @param int    $threadId Thread primary key.
     * @param int    $userId   Author user id (must match stored author).
     * @param string $title    New title (non-empty after trim).
     * @param string $body     New body (non-empty after trim).
     * @return bool True when a row was updated.
     */
    public function updateDiscussionThread(int $threadId, int $userId, string $title, string $body): bool
    {
        $title = trim($title);
        $body = trim($body);
        if ($title === '' || $body === '') {
            return false;
        }

        $stmt = $this->db->prepare(
            'UPDATE club_discussion_threads SET title = :t, body = :b, updated_at = CURRENT_TIMESTAMP
             WHERE thread_id = :id AND user_id = :u'
        );
        $stmt->execute(array('t' => $title, 'b' => $body, 'id' => $threadId, 'u' => $userId));

        return $stmt->rowCount() > 0;
    }

    /**
     * Deletes a thread only when authored by `userId` (matches legacy permissions model).
     *
     * @param int $threadId Thread primary key.
     * @param int $userId   Author user id.
     * @return bool True when a row was deleted.
     */
    public function deleteDiscussionThread(int $threadId, int $userId): bool
    {
        $stmt = $this->db->prepare(
            'DELETE FROM club_discussion_threads WHERE thread_id = :id AND user_id = :u'
        );
        $stmt->execute(array('id' => $threadId, 'u' => $userId));

        return $stmt->rowCount() > 0;
    }

    /**
     * Other Reader accounts only (for invites).
     *
     * @return list<array{user_id:int,username:string,email:string}>
     */
    public function listReadersForInvite(int $excludeUserId): array
    {
        $stmt = $this->db->prepare(
            "SELECT user_id, username, email FROM users WHERE user_id != :id AND role = 'Reader' ORDER BY username ASC"
        );
        $stmt->execute(array('id' => $excludeUserId));
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: array();
        $out = array();
        foreach ($rows as $r) {
            $out[] = array(
                'user_id' => (int) $r['user_id'],
                'username' => (string) $r['username'],
                'email' => (string) $r['email'],
            );
        }
        return $out;
    }

    /**
     * Remove membership (and saved “current read” for that club).
     */
    public function leaveClub(int $clubId, int $userId, ?string $email): bool
    {
        $this->db->prepare(
            'DELETE FROM club_member_current_read WHERE club_id = :cid AND user_id = :uid'
        )->execute(array('cid' => $clubId, 'uid' => $userId));

        $em = ($email !== null && $email !== '') ? $email : '';
        $stmt = $this->db->prepare(
            'DELETE FROM club_members WHERE club_id = :cid
             AND (user_id = :uid
                  OR (user_id IS NULL AND LENGTH(TRIM(:em_a)) > 0
                      AND TRIM(LOWER(email)) = TRIM(LOWER(:em_b))))'
        );
        $stmt->execute(
            array(
                'cid' => $clubId,
                'uid' => $userId,
                'em_a' => $em,
                'em_b' => $em,
            )
        );

        return $stmt->rowCount() > 0;
    }

    /**
     * Upserts (or clears) the member’s “current read” record for a club; empty title deletes the row.
     *
     * @param int         $clubId    Club id.
     * @param int         $userId    Member user id.
     * @param string      $bookTitle Display title (trimmed); empty means delete current read.
     * @param string|null $bookAuthor Optional author string (nullable).
     * @return bool True on successful execute paths (including successful delete-on-empty-title).
     */
    public function saveMyCurrentRead(int $clubId, int $userId, string $bookTitle, ?string $bookAuthor): bool
    {
        $title = trim($bookTitle);
        $author = $bookAuthor !== null ? trim($bookAuthor) : '';

        if ($title === '') {
            $this->db->prepare(
                'DELETE FROM club_member_current_read WHERE club_id = :c AND user_id = :u'
            )->execute(array('c' => $clubId, 'u' => $userId));

            return true;
        }

        $stmt = $this->db->prepare(
            'INSERT INTO club_member_current_read (club_id, user_id, book_title, book_author)
             VALUES (:c, :u, :t, :a)
             ON DUPLICATE KEY UPDATE book_title = VALUES(book_title),
               book_author = VALUES(book_author), updated_at = CURRENT_TIMESTAMP'
        );

        return $stmt->execute(
            array(
                'c' => $clubId,
                'u' => $userId,
                't' => $title,
                'a' => $author !== '' ? $author : null,
            )
        );
    }

    /**
     * Checks membership via `user_id` match or legacy email-only membership rows.
     *
     * @param int         $clubId Club id.
     * @param int         $userId User id.
     * @param string|null $email  Email fallback when legacy rows used email-only joins.
     * @return bool True if membership exists.
     */
    public function userIsMemberOfClub(int $clubId, int $userId, ?string $email): bool
    {
        $em = ($email !== null && $email !== '') ? $email : '';
        $stmt = $this->db->prepare(
            'SELECT 1 FROM club_members WHERE club_id = :cid
             AND (user_id = :uid
                  OR (user_id IS NULL AND LENGTH(TRIM(:em_a)) > 0
                      AND TRIM(LOWER(email)) = TRIM(LOWER(:em_b))))
             LIMIT 1'
        );
        $stmt->execute(
            array(
                'cid' => $clubId,
                'uid' => $userId,
                'em_a' => $em,
                'em_b' => $em,
            )
        );

        return (bool) $stmt->fetchColumn();
    }

    /**
     * Adds a member row keyed by `user_id` + email when not already present for the club.
     *
     * @param int    $clubId Club id.
     * @param int    $userId Member user id.
     * @param string $email  Email stored on membership row (legacy compatibility).
     * @return bool False when duplicate membership detected; true on insert.
     */
    public function addMember(int $clubId, int $userId, string $email): bool
    {
        $chk = $this->db->prepare(
            'SELECT 1 FROM club_members WHERE club_id = :club_id AND user_id = :uid LIMIT 1'
        );
        $chk->execute(array('club_id' => $clubId, 'uid' => $userId));
        if ($chk->fetch()) {
            return false;
        }
        $ins = $this->db->prepare(
            'INSERT INTO club_members (club_id, user_id, email) VALUES (:club_id, :uid, :email)'
        );
        $ins->execute(array('club_id' => $clubId, 'uid' => $userId, 'email' => $email));
        return true;
    }

    /**
     * Adds an email-only membership row (`user_id` NULL) when not already present for that club/email pair.
     *
     * @param int    $clubId Club id.
     * @param string $email  Invite email string (trim semantics handled by SQL comparisons).
     * @return bool False on duplicate; true on insert.
     */
    public function addMemberByEmailOnly(int $clubId, string $email): bool
    {
        $chk = $this->db->prepare(
            'SELECT 1 FROM club_members WHERE club_id = :club_id AND LOWER(TRIM(email)) = LOWER(TRIM(:email)) LIMIT 1'
        );
        $chk->execute(array('club_id' => $clubId, 'email' => $email));
        if ($chk->fetch()) {
            return false;
        }
        $ins = $this->db->prepare(
            'INSERT INTO club_members (club_id, user_id, email) VALUES (:club_id, NULL, :email)'
        );
        $ins->execute(array('club_id' => $clubId, 'email' => $email));
        return true;
    }

    /**
     * Creates a new reading club row and returns the generated club id.
     *
     * IMPORTANT: The SQL string inside this method must stay identical to the legacy query previously embedded in `public/member.php`.
     *
     * @param string      $name          Club name (required by caller).
     * @param string|null $description   Optional club description (nullable).
     * @param string|null $creatorEmail  Optional creator email (nullable).
     * @param int|null    $creatorUserId Optional creator user id (nullable).
     * @return int Newly created `reading_clubs.club_id`.
     */
    public function createClub(string $name, ?string $description, ?string $creatorEmail, ?int $creatorUserId): int
    {
        $stmt = $this->db->prepare(
            'INSERT INTO reading_clubs (name, description, creator_email, creator_user_id) VALUES (:name, :desc, :cemail, :cuid)'
        );
        $stmt->execute(array(
            ':name' => $name,
            ':desc' => $description,
            ':cemail' => $creatorEmail,
            ':cuid' => $creatorUserId,
        ));

        return (int) $this->db->lastInsertId();
    }

    /**
     * Lists all clubs for the “Available Clubs” section (newest first).
     *
     * IMPORTANT: The SQL string inside this method must stay identical to the legacy query previously embedded in `public/member.php`.
     *
     * @return list<array<string,mixed>> Club rows.
     */
    public function listAllClubsNewestFirst(): array
    {
        return $this->db
            ->query('SELECT club_id, name, description, creator_email, created_at FROM reading_clubs ORDER BY created_at DESC')
            ->fetchAll() ?: array();
    }
}

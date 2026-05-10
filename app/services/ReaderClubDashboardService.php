<?php

/**
 * FILE: ReaderClubDashboardService.php
 * PURPOSE: Loads “my clubs” summaries for signed-in readers including per-club member current-read aggregates.
 * USED BY: `HomeController`, `public/orders.php`.
 * DESIGN PATTERN: None — coordinates `User` + `ReadingClub` models without SQL in controllers.
 */

require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../models/ReadingClub.php';

/**
 * ReaderClubDashboardService
 *
 * Encapsulates the reader home page "my clubs" loading flow.
 * This keeps controller logic focused on HTTP/page orchestration.
 */
class ReaderClubDashboardService
{
    public function __construct(private PDO $db)
    {
    }

    /**
     * Build the club view model for a reader account.
     *
     * @return list<array<string,mixed>>
     */
    public function getReaderClubs(int $userId): array
    {
        $readerClub = new ReadingClub($this->db);
        $readerClub->ensureSchema();

        $user = (new User($this->db))->findById($userId);
        if (!$user) {
            return array();
        }

        $myClubs = $readerClub->findClubsForUser((int) $user['user_id'], (string) $user['email']);
        $readsByClub = $readerClub->getMemberReadsForClubs(array_column($myClubs, 'club_id'));

        foreach ($myClubs as &$clubRow) {
            $clubRow['member_reads'] = $readsByClub[(int) $clubRow['club_id']] ?? array();
        }
        unset($clubRow);

        return $myClubs;
    }
}

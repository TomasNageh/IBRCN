<?php

/**
 * FILE: AuditLogRepository.php
 * PURPOSE: Inserts immutable audit trail rows describing authenticated actions (login, registration, etc.).
 * USED BY: `AuthController` after successful login/register events.
 * DESIGN PATTERN: None — append-only repository around `audit_log` table.
 */

// Persists actor/action tuples for compliance traceability without exposing delete/update helpers publicly (repository).
class AuditLogRepository
{
    /**
     * Captures PDO wired through controllers/services constructing audit writers during request lifecycle.
     *
     * @param PDO $databaseConnection Shared singleton-backed connection used for INSERT-only statements.
     * @return void
     */
    public function __construct(private PDO $databaseConnection)
    {
    }

    /**
     * Writes one audit row after trimming actor role/action strings to database column width tolerances.
     *
     * @param int|null    $actorId          Acting user id when known (nullable for future system actions — unused today).
     * @param string      $actorRole        Role label persisted for quick filtering (trimmed to 20 chars).
     * @param string      $actionType       Stable machine-ish verb describing event (`login_success`, etc.) trimmed ≤60 chars.
     * @param int|null    $affectedRecordId Optional primary key touched by action for forensic joins (nullable).
     * @param string|null $affectedTable    Optional table name pointer trimmed ≤60 chars for clarity in admin viewers.
     * @return void
     */
    public function write(
        ?int $actorId,
        string $actorRole,
        string $actionType,
        ?int $affectedRecordId = null,
        ?string $affectedTable = null
    ): void {
        $role = substr(trim($actorRole), 0, 20);
        if ($role === '') {
            $role = 'Unknown';
        }
        $action = substr(trim($actionType), 0, 60);
        if ($action === '') {
            return;
        }
        $tableName = $affectedTable !== null ? substr(trim($affectedTable), 0, 60) : null;
        if ($tableName === '') {
            $tableName = null;
        }

        try {
            $statement = $this->databaseConnection->prepare(
                'INSERT INTO audit_log (actor_id, actor_role, action_type, affected_record_id, affected_table)
                 VALUES (:aid, :role, :act, :rec_id, :tbl)'
            );
            $statement->execute(array(
                'aid' => $actorId > 0 ? $actorId : null,
                'role' => $role,
                'act' => $action,
                'rec_id' => $affectedRecordId !== null && $affectedRecordId > 0 ? $affectedRecordId : null,
                'tbl' => $tableName,
            ));
        } catch (Throwable $exception) {
            error_log('audit_log insert: ' . $exception->getMessage());
        }
    }
}

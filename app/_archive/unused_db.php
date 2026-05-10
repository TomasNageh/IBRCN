<?php
// ARCHIVED: This file contained OpenCon() which was never called
// anywhere in the codebase. Kept here for reference only.
// Do not require() or include() this file in production.

require_once __DIR__ . '/../../app/bootstrap.php';

class LegacyPdoAdapter
{
    private PDO $pdo;
    public string $error = '';

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function query(string $sql): bool
    {
        try {
            $this->pdo->exec($sql);
            return true;
        } catch (Throwable $e) {
            $this->error = $e->getMessage();
            return false;
        }
    }

    public function close(): void
    {
        // PDO connection is managed by singleton lifecycle.
    }
}

function OpenCon(): LegacyPdoAdapter
{
    return new LegacyPdoAdapter(DB::getInstance());
}


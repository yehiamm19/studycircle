<?php

declare(strict_types=1);

namespace App\Models;

class UseCase
{
    public static function forGroup(int $groupId): array
    {
        $stmt = db()->prepare('SELECT * FROM use_cases WHERE group_id = ? ORDER BY code ASC');
        $stmt->execute([$groupId]);

        return $stmt->fetchAll() ?: [];
    }

    public static function create(int $groupId, string $code, string $title, string $description = ''): int
    {
        db()->prepare('INSERT INTO use_cases (group_id, code, title, description, updated_at) VALUES (?,?,?,?,datetime("now"))')
            ->execute([$groupId, $code, $title, $description]);

        return (int) db()->lastInsertId();
    }
}

<?php

declare(strict_types=1);

namespace App\Models;

class Resource
{
    public static function forGroup(int $groupId): array
    {
        $stmt = db()->prepare('
            SELECT r.*, u.name as uploader_name FROM resources r JOIN users u ON r.user_id = u.id
            WHERE r.group_id = ? ORDER BY r.created_at DESC
        ');
        $stmt->execute([$groupId]);
        return $stmt->fetchAll();
    }

    public static function find(int $id): ?array
    {
        $stmt = db()->prepare('SELECT * FROM resources WHERE id = ?');
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    public static function create(array $data): int
    {
        $stmt = db()->prepare('INSERT INTO resources (group_id, user_id, title, category, filename, original_name, mime_type, file_size) VALUES (?,?,?,?,?,?,?,?)');
        $stmt->execute([
            $data['group_id'], $data['user_id'], $data['title'], $data['category'],
            $data['filename'], $data['original_name'], $data['mime_type'], $data['file_size'],
        ]);
        return (int) db()->lastInsertId();
    }

    public static function delete(int $id): ?array
    {
        $resource = self::find($id);
        if ($resource) {
            db()->prepare('DELETE FROM resources WHERE id = ?')->execute([$id]);
        }
        return $resource;
    }
}


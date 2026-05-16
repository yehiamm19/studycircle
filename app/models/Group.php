<?php

declare(strict_types=1);

namespace App\Models;

class Group
{
    public static function find(int $id): ?array
    {
        $stmt = db()->prepare('SELECT g.*, u.name as owner_name FROM groups g JOIN users u ON g.owner_id = u.id WHERE g.id = ?');
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    public static function forUser(int $userId): array
    {
        $stmt = db()->prepare('
            SELECT g.*, 
                (SELECT COUNT(*) FROM group_members WHERE group_id = g.id) as member_count,
                (SELECT COUNT(*) FROM tasks WHERE group_id = g.id AND status != "completed") as open_tasks
            FROM groups g
            JOIN group_members gm ON g.id = gm.group_id
            WHERE gm.user_id = ?
            ORDER BY g.updated_at DESC
        ');
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    }

    public static function create(array $data): int
    {
        $code = bin2hex(random_bytes(6));
        $stmt = db()->prepare('INSERT INTO groups (name, description, color, icon, owner_id, invite_code) VALUES (?,?,?,?,?,?)');
        $stmt->execute([$data['name'], $data['description'] ?? '', $data['color'] ?? 'indigo', $data['icon'] ?? 'book-open', $data['owner_id'], $code]);
        $id = (int) db()->lastInsertId();
        db()->prepare('INSERT INTO group_members (group_id, user_id, role) VALUES (?,?,?)')->execute([$id, $data['owner_id'], 'owner']);
        return $id;
    }

    public static function update(int $id, array $data): void
    {
        db()->prepare('UPDATE groups SET name = ?, description = ?, color = ?, icon = ?, updated_at = datetime("now") WHERE id = ?')
            ->execute([$data['name'], $data['description'] ?? '', $data['color'] ?? 'indigo', $data['icon'] ?? 'book-open', $id]);
    }

    public static function delete(int $id): void
    {
        db()->prepare('DELETE FROM groups WHERE id = ?')->execute([$id]);
    }

    public static function isMember(int $groupId, int $userId): bool
    {
        $stmt = db()->prepare('SELECT 1 FROM group_members WHERE group_id = ? AND user_id = ?');
        $stmt->execute([$groupId, $userId]);
        return (bool) $stmt->fetch();
    }

    public static function members(int $groupId): array
    {
        $stmt = db()->prepare('
            SELECT u.id, u.name, u.email, u.avatar, u.xp, u.public_profile_slug, gm.role, gm.scrum_role, gm.joined_at
            FROM group_members gm JOIN users u ON gm.user_id = u.id
            WHERE gm.group_id = ? ORDER BY gm.role, u.name
        ');
        $stmt->execute([$groupId]);
        return $stmt->fetchAll();
    }

    /** @return ?array{id: int|string, role: string, scrum_role: string, ...} */
    public static function membership(int $groupId, int $userId): ?array
    {
        $stmt = db()->prepare('SELECT * FROM group_members WHERE group_id = ? AND user_id = ? LIMIT 1');
        $stmt->execute([$groupId, $userId]);
        $row = $stmt->fetch();

        return $row ?: null;
    }

    public const SCRUM_ROLES = ['product_owner', 'scrum_master', 'developer'];

    public static function sanitizeScrumRole(string $role): string
    {
        $r = strtolower(trim($role));

        return in_array($r, self::SCRUM_ROLES, true) ? $r : 'developer';
    }

    /** Owners and admins assign Scrum ceremonial roles only. */
    public static function canAssignScrumRoles(?array $membershipRow): bool
    {
        if (!$membershipRow) {
            return false;
        }

        return in_array($membershipRow['role'] ?? '', ['owner', 'admin'], true);
    }

    /** Scrum structure (sprints, requirements, use cases): owners, admins, SM, PO. */
    public static function canManageScrumArtifacts(?array $membershipRow): bool
    {
        if (!$membershipRow) {
            return false;
        }
        if (in_array($membershipRow['role'] ?? '', ['owner', 'admin'], true)) {
            return true;
        }
        $sr = $membershipRow['scrum_role'] ?? 'developer';

        return in_array($sr, ['scrum_master', 'product_owner'], true);
    }

    public static function updateMemberScrumRole(int $groupId, int $userId, string $scrumRole): void
    {
        db()->prepare('UPDATE group_members SET scrum_role = ? WHERE group_id = ? AND user_id = ?')->execute([
            self::sanitizeScrumRole($scrumRole),
            $groupId,
            $userId,
        ]);
    }

    public static function findByInvite(string $code): ?array
    {
        $stmt = db()->prepare('SELECT * FROM groups WHERE invite_code = ?');
        $stmt->execute([$code]);
        return $stmt->fetch() ?: null;
    }

    public static function addMember(int $groupId, int $userId, string $role = 'member'): void
    {
        db()->prepare('INSERT OR IGNORE INTO group_members (group_id, user_id, role) VALUES (?,?,?)')
            ->execute([$groupId, $userId, $role]);
    }

    public static function removeMember(int $groupId, int $userId): void
    {
        db()->prepare('DELETE FROM group_members WHERE group_id = ? AND user_id = ? AND role != "owner"')
            ->execute([$groupId, $userId]);
    }
}


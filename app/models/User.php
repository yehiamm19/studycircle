<?php

declare(strict_types=1);

namespace App\Models;

class User
{
    public static function find(int $id): ?array
    {
        $stmt = db()->prepare('SELECT id, name, email, role, avatar, bio, xp, streak, created_at, public_profile_slug FROM users WHERE id = ?');
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    public static function findByEmail(string $email): ?array
    {
        $stmt = db()->prepare('SELECT * FROM users WHERE email = ?');
        $stmt->execute([$email]);
        return $stmt->fetch() ?: null;
    }

    public static function create(array $data): int
    {
        $stmt = db()->prepare('INSERT INTO users (name, email, password) VALUES (?,?,?)');
        $stmt->execute([$data['name'], $data['email'], password_hash($data['password'], PASSWORD_DEFAULT)]);
        $id = (int) db()->lastInsertId();
        self::ensurePublicProfileSlug($id);

        return $id;
    }

    public static function update(int $id, array $data): void
    {
        $fields = [];
        $values = [];
        foreach (['name', 'bio', 'avatar'] as $f) {
            if (array_key_exists($f, $data)) {
                $fields[] = "$f = ?";
                $values[] = $data[$f];
            }
        }
        if (empty($fields)) return;
        $fields[] = 'updated_at = datetime("now")';
        $values[] = $id;
        db()->prepare('UPDATE users SET ' . implode(', ', $fields) . ' WHERE id = ?')->execute($values);
    }

    public static function setResetToken(int $id, string $token): void
    {
        db()->prepare('UPDATE users SET reset_token = ?, reset_expires = datetime("now", "+1 hour") WHERE id = ?')
            ->execute([$token, $id]);
    }

    public static function findByResetToken(string $token): ?array
    {
        $stmt = db()->prepare('SELECT * FROM users WHERE reset_token = ? AND reset_expires > datetime("now")');
        $stmt->execute([$token]);
        return $stmt->fetch() ?: null;
    }

    public static function updatePassword(int $id, string $password): void
    {
        db()->prepare('UPDATE users SET password = ?, reset_token = NULL, reset_expires = NULL WHERE id = ?')
            ->execute([password_hash($password, PASSWORD_DEFAULT), $id]);
    }

    public static function leaderboard(int $limit = 10): array
    {
        $stmt = db()->prepare('SELECT id, name, avatar, xp, streak, public_profile_slug FROM users ORDER BY xp DESC LIMIT ?');
        $stmt->execute([$limit]);
        return $stmt->fetchAll();
    }

    public static function totalCount(): int
    {
        return (int) db()->query('SELECT COUNT(*) FROM users')->fetchColumn();
    }

    /** 1-based XP rank (#1 = highest XP among all users). Ties share the same numeric rank (“first among equals”). */
    public static function xpRank(int $userId): int
    {
        $stmt = db()->prepare('SELECT xp FROM users WHERE id = ?');
        $stmt->execute([$userId]);
        $row = $stmt->fetch();
        if (!$row) {
            return 0;
        }
        $xp = (int) ($row['xp'] ?? 0);
        $c = db()->prepare('SELECT COUNT(*) FROM users WHERE xp > ?');
        $c->execute([$xp]);
        return (int) $c->fetchColumn() + 1;
    }

    public static function refreshSession(int $id): ?array
    {
        return self::find($id);
    }

    public static function allForAdmin(string $search = '', int $limit = 100): array
    {
        if ($search !== '') {
            $stmt = db()->prepare('
                SELECT id, name, email, role, avatar, xp, streak, created_at
                FROM users
                WHERE name LIKE ? OR email LIKE ?
                ORDER BY created_at DESC LIMIT ?
            ');
            $like = '%' . $search . '%';
            $stmt->execute([$like, $like, $limit]);
            return $stmt->fetchAll();
        }
        $stmt = db()->prepare('
            SELECT id, name, email, role, avatar, xp, streak, created_at
            FROM users ORDER BY created_at DESC LIMIT ?
        ');
        $stmt->execute([$limit]);
        return $stmt->fetchAll();
    }

    public static function updateAdmin(int $id, array $data): void
    {
        $allowed = ['name', 'email', 'role', 'bio', 'xp', 'streak'];
        $fields = [];
        $values = [];
        foreach ($allowed as $f) {
            if (array_key_exists($f, $data)) {
                $fields[] = "$f = ?";
                $values[] = $data[$f];
            }
        }
        if (empty($fields)) {
            return;
        }
        $fields[] = 'updated_at = datetime("now")';
        $values[] = $id;
        db()->prepare('UPDATE users SET ' . implode(', ', $fields) . ' WHERE id = ?')->execute($values);
    }

    public static function delete(int $id): bool
    {
        $stmt = db()->prepare('DELETE FROM users WHERE id = ?');
        $stmt->execute([$id]);
        return $stmt->rowCount() > 0;
    }

    public static function ensurePublicProfileSlugColumn(): void
    {
        try {
            $cols = db()->query('PRAGMA table_info(users)')->fetchAll(\PDO::FETCH_ASSOC);
        } catch (\Throwable) {
            return;
        }
        $hasColumn = false;
        foreach ($cols as $col) {
            if (($col['name'] ?? '') === 'public_profile_slug') {
                $hasColumn = true;
                break;
            }
        }
        if (!$hasColumn) {
            /** SQLite forbids UNIQUE on ALTER TABLE ADD COLUMN — enforce via index below */
            db()->exec('ALTER TABLE users ADD COLUMN public_profile_slug TEXT');
        }
        db()->exec('CREATE UNIQUE INDEX IF NOT EXISTS uniq_users_public_profile_slug ON users(public_profile_slug)');
    }

    public static function generateUniqueSlug(): string
    {
        for ($i = 0; $i < 16; $i++) {
            $slug = strtolower(bin2hex(random_bytes(12)));
            $chk = db()->prepare('SELECT 1 FROM users WHERE public_profile_slug = ?');
            $chk->execute([$slug]);
            if (!$chk->fetch()) {
                return $slug;
            }
        }

        return strtolower(bin2hex(random_bytes(16)));
    }

    /**
     * Creates a slug once per user; never rotates on profile edits.
     */
    public static function ensurePublicProfileSlug(int $userId): string
    {
        $stmt = db()->prepare('SELECT public_profile_slug FROM users WHERE id = ?');
        $stmt->execute([$userId]);
        $row = $stmt->fetch();
        if (!$row) {
            return '';
        }
        $existing = trim((string) ($row['public_profile_slug'] ?? ''));
        if ($existing !== '') {
            return strtolower($existing);
        }
        $slug = self::generateUniqueSlug();
        db()->prepare('UPDATE users SET public_profile_slug = ?, updated_at = datetime("now") WHERE id = ?')->execute([$slug, $userId]);

        return $slug;
    }

    /** Fill slugs for accounts created before the column existed. */
    public static function backfillMissingPublicProfileSlugs(): void
    {
        try {
            $rows = db()->query('SELECT id FROM users WHERE public_profile_slug IS NULL OR trim(public_profile_slug) = \'\'')->fetchAll();
        } catch (\Throwable) {
            return;
        }
        foreach ($rows as $r) {
            $id = (int) ($r['id'] ?? 0);
            if ($id > 0) {
                self::ensurePublicProfileSlug($id);
            }
        }
    }

    public static function findByPublicProfileSlug(string $slug): ?array
    {
        $slug = strtolower(trim($slug));
        if ($slug === '' || !preg_match('/^[a-f0-9]{10,128}$/', $slug)) {
            return null;
        }
        $stmt = db()->prepare('
            SELECT id, name, email, role, avatar, bio, xp, streak, created_at, public_profile_slug
            FROM users
            WHERE lower(public_profile_slug) = ?
        ');
        $stmt->execute([$slug]);
        $u = $stmt->fetch();

        return $u ?: null;
    }

    public static function publicProfileUrlPath(int $userId): string
    {
        $slug = self::ensurePublicProfileSlug($userId);

        return '/p/' . $slug;
    }
}


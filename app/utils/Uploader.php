<?php

declare(strict_types=1);

namespace App\Utils;

class Uploader
{
    public static function upload(array $file, string $directory, array $allowed, int $maxSize): array
    {
        if ($file['error'] !== UPLOAD_ERR_OK) {
            return ['error' => self::uploadErrorMessage($file['error'])];
        }

        if ($file['size'] > $maxSize) {
            return ['error' => 'File exceeds maximum size of ' . round($maxSize / 1048576, 1) . 'MB.'];
        }

        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, $allowed, true)) {
            return ['error' => 'File type not allowed. Allowed: ' . implode(', ', $allowed)];
        }

        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mime = $finfo->file($file['tmp_name']);
        $validMimes = [
            'pdf' => ['application/pdf'],
            'jpg' => ['image/jpeg'],
            'jpeg' => ['image/jpeg'],
            'png' => ['image/png'],
            'webp' => ['image/webp'],
        ];

        if (isset($validMimes[$ext]) && !in_array($mime, $validMimes[$ext], true)) {
            return ['error' => 'File content does not match extension.'];
        }

        if (!is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        $filename = bin2hex(random_bytes(16)) . '.' . $ext;
        $dest = rtrim($directory, '/') . '/' . $filename;

        if (!move_uploaded_file($file['tmp_name'], $dest)) {
            return ['error' => 'Failed to save uploaded file.'];
        }

        return [
            'filename' => $filename,
            'original_name' => basename($file['name']),
            'mime_type' => $mime,
            'file_size' => $file['size'],
            'extension' => $ext,
        ];
    }

    public static function delete(string $directory, string $filename): void
    {
        $path = rtrim($directory, '/') . '/' . $filename;
        if ($filename && file_exists($path)) {
            unlink($path);
        }
    }

    private static function uploadErrorMessage(int $code): string
    {
        return match ($code) {
            UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE => 'File is too large.',
            UPLOAD_ERR_PARTIAL => 'File was only partially uploaded.',
            UPLOAD_ERR_NO_FILE => 'No file was uploaded.',
            default => 'Upload failed. Please try again.',
        };
    }
}


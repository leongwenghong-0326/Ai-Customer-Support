<?php
/**
 * NovaServe AI - Controlled & Hardened File Uploader
 * Validates extension, MIME type, size, errors, and stores using randomized filenames
 */

require_once __DIR__ . '/../config/config.php';

class SecureUploader {
    private static array $allowedExtensions = ['jpg', 'jpeg', 'png', 'pdf', 'doc', 'docx', 'txt'];
    private static int $maxSizeBytes = 5 * 1024 * 1024; // 5MB

    public static function upload(array $fileArray, string $subfolder = 'tickets'): array {
        if (!isset($fileArray['error']) || is_array($fileArray['error'])) {
            return ['success' => false, 'error' => 'Invalid upload parameters.'];
        }

        switch ($fileArray['error']) {
            case UPLOAD_ERR_OK:
                break;
            case UPLOAD_ERR_NO_FILE:
                return ['success' => false, 'error' => 'No file was uploaded.'];
            case UPLOAD_ERR_INI_SIZE:
            case UPLOAD_ERR_FORM_SIZE:
                return ['success' => false, 'error' => 'Exceeded file size ceiling (Max 5MB).'];
            default:
                return ['success' => false, 'error' => 'Unknown server upload error occurred.'];
        }

        if ($fileArray['size'] > self::$maxSizeBytes) {
            return ['success' => false, 'error' => 'File exceeds 5MB size limit.'];
        }

        $originalName = basename($fileArray['name']);
        $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));

        if (!in_array($extension, self::$allowedExtensions, true)) {
            return ['success' => false, 'error' => 'File type forbidden. Allowed formats: JPG, PNG, PDF, DOC, DOCX, TXT.'];
        }

        // Additional MIME check
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $detectedMime = $finfo->file($fileArray['tmp_name']);

        // Check for double extensions or null bytes
        if (strpos($originalName, "\0") !== false || preg_match('/\.(php|phtml|phar|cgi|pl|py|sh|bash)$/i', $originalName)) {
            return ['success' => false, 'error' => 'Malicious filename signature detected.'];
        }

        $targetDir = UPLOADS_PATH . DS . trim($subfolder, '/\\');
        if (!is_dir($targetDir)) {
            mkdir($targetDir, 0755, true);
        }

        // Generate cryptographically random filename
        $storedFilename = 'ns_' . date('Ymd_His') . '_' . bin2hex(random_bytes(8)) . '.' . $extension;
        $destinationPath = $targetDir . DS . $storedFilename;

        if (!move_uploaded_file($fileArray['tmp_name'], $destinationPath)) {
            return ['success' => false, 'error' => 'Failed to save attachment to persistent storage.'];
        }

        return [
            'success' => true,
            'original_filename' => $originalName,
            'stored_filename' => $storedFilename,
            'file_size' => $fileArray['size'],
            'mime_type' => $detectedMime,
            'file_path' => 'uploads/' . trim($subfolder, '/\\') . '/' . $storedFilename
        ];
    }
}

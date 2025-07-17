<?php
/*
File handling file = this is going to take care of pictures of the candidates if they are going to be using a picture.
*/

class FileHandler
{
    private static $allowedMimeTypes;
    private static $allowedExtensions;
    private static $maxFileSize;
    
    public static function init() {
        $config = require __DIR__ . '/config.php';
        
        self::$allowedMimeTypes = $config['file_upload']['allowed_mime_types'] ?? [
            'image/jpeg',
            'image/jpg', 
            'image/png',
            'image/gif'
        ];
        
        self::$allowedExtensions = $config['file_upload']['allowed_extensions'] ?? [
            'jpg',
            'jpeg',
            'png',
            'gif'
        ];
        
        self::$maxFileSize = $config['file_upload']['max_size'] ?? 2097152; // 2MB in bytes
    }
    
    public static function uploadFile(array $file, string $relativeDir, string &$error = null): ?string
    {
        if (empty(self::$allowedMimeTypes)) {
            self::init();
        }
        
        if (!isset($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
            $error = 'No file was uploaded';
            error_log($error);
            return null;
        }

        if ($file['error'] !== UPLOAD_ERR_OK) {
            $errorMessages = [
                UPLOAD_ERR_INI_SIZE => 'The uploaded file exceeds the upload_max_filesize directive in php.ini',
                UPLOAD_ERR_FORM_SIZE => 'The uploaded file exceeds the MAX_FILE_SIZE directive in the HTML form',
                UPLOAD_ERR_PARTIAL => 'The uploaded file was only partially uploaded',
                UPLOAD_ERR_NO_FILE => 'No file was uploaded',
                UPLOAD_ERR_NO_TMP_DIR => 'Missing a temporary folder',
                UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk',
                UPLOAD_ERR_EXTENSION => 'A PHP extension stopped the file upload'
            ];
            
            $error = 'Upload error: ' . ($errorMessages[$file['error']] ?? 'Unknown error');
            error_log($error);
            return null;
        }

        if ($file['size'] > self::$maxFileSize) {
            $error = 'File is too large. Maximum size is ' . (self::$maxFileSize / 1024 / 1024) . 'MB';
            error_log($error);
            return null;
        }
        
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, self::$allowedExtensions)) {
            $error = 'Invalid file type. Allowed types: ' . implode(', ', self::$allowedExtensions);
            error_log($error);
            return null;
        }
        
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mime = $finfo->file($file['tmp_name']);
        
        if (!in_array($mime, self::$allowedMimeTypes)) {
            $error = 'Invalid file type detected: ' . $mime;
            error_log($error);
            return null;
        }
        
        if (!function_exists('getimagesize') || getimagesize($file['tmp_name']) === false) {
            $error = 'Uploaded file is not a valid image';
            error_log($error);
            return null;
        }
        
        $relativeDir = ltrim(str_replace(['..', './', '/.'], '', $relativeDir), '/');
        
        $baseDir = __DIR__ . "/../";
        $uploadDir = realpath($baseDir . $relativeDir);
        
        if ($uploadDir === false) {
            if (!mkdir($baseDir . $relativeDir, 0755, true)) {
                $error = "Failed to create upload directory: {$baseDir}{$relativeDir}";
                error_log($error);
                return null;
            }
            $uploadDir = realpath($baseDir . $relativeDir);
        }
        
        if (strpos($uploadDir, realpath($baseDir)) !== 0) {
            $error = "Security violation: Upload directory is outside project path";
            error_log($error);
            return null;
        }

        if (!is_writable($uploadDir)) {
            $error = "Upload directory is not writable: {$uploadDir}";
            error_log($error);
            return null;
        }

        $filename = SecurityHelper::secureFilename($file['name'], $ext);
        $dest = $uploadDir . DIRECTORY_SEPARATOR . $filename;

        if (!move_uploaded_file($file['tmp_name'], $dest)) {
            $error = 'Error moving uploaded file to: ' . $dest;
            error_log($error);
            return null;
        }

        return rtrim($relativeDir, '/') . '/' . $filename;
    }

    public static function deleteFile(string $relativePath): bool {
        $relativePath = ltrim(str_replace(['..', './', '/.'], '', $relativePath), '/');
        
        if (empty($relativePath)) {
            return false;
        }
        
        $full = __DIR__ . "/../{$relativePath}";
        
        if (strpos(realpath($full), realpath(__DIR__ . '/..')) !== 0) {
            error_log("Security violation: Attempted to delete file outside project path: {$relativePath}");
            return false;
        }
        
        if (file_exists($full)) {
            return unlink($full);
        }
        return true;
    }
}

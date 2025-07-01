<?php
/*
File handling file = this is going to take care of pictures of the candidates if they are going to be using a picture.
*/

class FileHandler
{
    public static function uploadFile(array $file, string $relativeDir, string $error = null): ?string
    {
        // Build absolute target directory under project root
        $uploadDir = realpath(__DIR__ . "/../{$relativeDir}");
        if ($uploadDir === false) {
            // Directory doesn't exist – try to create it
            $base = __DIR__ . "/../";
            if (!mkdir($base . $relativeDir, 0777, true)) {
                $error = "Failed to create upload directory: {$base}{$relativeDir}";
                error_log($error);
                return null;
            }
            $uploadDir = realpath($base . $relativeDir);
        }

        // Check write permissions
        if (!is_writable($uploadDir)) {
            $error = "Upload directory is not writable: {$uploadDir}";
            error_log($error);
            return null;
        }

        // Check PHP upload error
        if ($file['error'] !== UPLOAD_ERR_OK) {
            $error = 'Upload error code: ' . $file['error'];
            error_log($error);
            return null;
        }

        // Validate image
        if (getimagesize($file['tmp_name']) === false) {
            $error = 'Uploaded file is not a valid image.';
            error_log($error);
            return null;
        }

        // Generate unique filename
        $ext      = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = uniqid('candidate_', true) . '.' . $ext;
        $dest     = $uploadDir . DIRECTORY_SEPARATOR . $filename;

        // Attempt to move uploaded file
        if (!move_uploaded_file($file['tmp_name'], $dest)) {
            $error = 'Error moving uploaded file to: ' . $dest;
            error_log($error);
            return null;
        }

        // Return the path relative to the web root
        return rtrim($relativeDir, '/') . '/' . $filename;
    }

    public static function deleteFile(string $relativePath): bool {
        $full = __DIR__ . "/../{$relativePath}";
        if (file_exists($full)) {
            return unlink($full);
        }
        return true;
    }
}

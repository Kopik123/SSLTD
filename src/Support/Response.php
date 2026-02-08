<?php

namespace App\Support;

/**
 * Response Helper Class
 * 
 * Provides standardized HTTP response methods for consistent API/web responses
 */
class Response
{
    /**
     * Send JSON response
     *
     * @param mixed $data Response data
     * @param int $statusCode HTTP status code
     * @param array $headers Additional headers
     * @return never
     */
    public static function json($data, int $statusCode = 200, array $headers = []): never
    {
        http_response_code($statusCode);
        header('Content-Type: application/json');
        
        foreach ($headers as $key => $value) {
            header("$key: $value");
        }
        
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    /**
     * Send success JSON response
     *
     * @param mixed $data Response data
     * @param string|null $message Success message
     * @param int $statusCode HTTP status code
     * @return never
     */
    public static function success($data = null, ?string $message = null, int $statusCode = 200): never
    {
        $response = ['success' => true];
        
        if ($message !== null) {
            $response['message'] = $message;
        }
        
        if ($data !== null) {
            $response['data'] = $data;
        }
        
        self::json($response, $statusCode);
    }

    /**
     * Send error JSON response
     *
     * @param string $message Error message
     * @param int $statusCode HTTP status code
     * @param array|null $errors Validation errors
     * @return never
     */
    public static function error(string $message, int $statusCode = 400, ?array $errors = null): never
    {
        $response = [
            'success' => false,
            'message' => $message
        ];
        
        if ($errors !== null) {
            $response['errors'] = $errors;
        }
        
        self::json($response, $statusCode);
    }

    /**
     * Send 404 Not Found response
     *
     * @param string $message Error message
     * @return never
     */
    public static function notFound(string $message = 'Resource not found'): never
    {
        self::error($message, 404);
    }

    /**
     * Send 401 Unauthorized response
     *
     * @param string $message Error message
     * @return never
     */
    public static function unauthorized(string $message = 'Unauthorized'): never
    {
        self::error($message, 401);
    }

    /**
     * Send 403 Forbidden response
     *
     * @param string $message Error message
     * @return never
     */
    public static function forbidden(string $message = 'Forbidden'): never
    {
        self::error($message, 403);
    }

    /**
     * Send 422 Unprocessable Entity response (validation errors)
     *
     * @param array $errors Validation errors
     * @param string $message Error message
     * @return never
     */
    public static function validationError(array $errors, string $message = 'Validation failed'): never
    {
        self::error($message, 422, $errors);
    }

    /**
     * Send 500 Internal Server Error response
     *
     * @param string $message Error message
     * @return never
     */
    public static function serverError(string $message = 'Internal server error'): never
    {
        self::error($message, 500);
    }

    /**
     * Redirect to URL
     *
     * @param string $url Target URL
     * @param int $statusCode HTTP status code (301 or 302)
     * @return never
     */
    public static function redirect(string $url, int $statusCode = 302): never
    {
        http_response_code($statusCode);
        header("Location: $url");
        exit;
    }

    /**
     * Download file
     *
     * @param string $filePath Path to file
     * @param string|null $filename Download filename (null = use original)
     * @param string $contentType MIME type
     * @return never
     */
    public static function download(string $filePath, ?string $filename = null, string $contentType = 'application/octet-stream'): never
    {
        if (!file_exists($filePath)) {
            self::notFound('File not found');
        }
        
        $filename = $filename ?? basename($filePath);
        
        header("Content-Type: $contentType");
        header("Content-Disposition: attachment; filename=\"$filename\"");
        header("Content-Length: " . filesize($filePath));
        
        readfile($filePath);
        exit;
    }
}

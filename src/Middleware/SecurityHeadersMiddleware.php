<?php

namespace App\Middleware;

use App\Context;
use App\Http\Request;

/**
 * SecurityHeadersMiddleware
 * 
 * Adds security headers to HTTP responses to protect against common vulnerabilities.
 * 
 * Headers added:
 * - X-Frame-Options: Prevents clickjacking attacks
 * - X-Content-Type-Options: Prevents MIME type sniffing
 * - Referrer-Policy: Controls referrer information
 * - Permissions-Policy: Controls browser features
 * - X-XSS-Protection: Legacy XSS protection (for older browsers)
 * 
 * @package App\Middleware
 */
class SecurityHeadersMiddleware
{
    /**
     * Handle the request and add security headers
     *
     * @param Request $request The HTTP request
     * @param array $args Route arguments
     * @param Context $context Application context
     * @param callable $next Next middleware in the chain
     * @return mixed
     */
    public function handle(Request $request, array $args, Context $context, callable $next): mixed
    {
        // Add security headers
        header('X-Frame-Options: DENY');
        header('X-Content-Type-Options: nosniff');
        header('Referrer-Policy: strict-origin-when-cross-origin');
        header('X-XSS-Protection: 1; mode=block');
        
        // Permissions-Policy (previously Feature-Policy)
        $permissionsPolicy = implode(', ', [
            'camera=()',
            'microphone=()',
            'geolocation=()',
            'payment=()',
            'usb=()',
            'magnetometer=()',
            'accelerometer=()',
            'gyroscope=()'
        ]);
        header("Permissions-Policy: {$permissionsPolicy}");
        
        // Content-Security-Policy (restrictive default)
        // Note: This should be customized based on your application's needs
        $csp = implode('; ', [
            "default-src 'self'",
            "script-src 'self' 'unsafe-inline' 'unsafe-eval'", // Adjust based on your needs
            "style-src 'self' 'unsafe-inline'", // Adjust based on your needs
            "img-src 'self' data: https:",
            "font-src 'self' data:",
            "connect-src 'self'",
            "frame-ancestors 'none'",
            "base-uri 'self'",
            "form-action 'self'"
        ]);
        header("Content-Security-Policy: {$csp}");
        
        // Strict-Transport-Security (HSTS) - only in production with HTTPS
        if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
            header('Strict-Transport-Security: max-age=31536000; includeSubDomains; preload');
        }
        
        return $next($request, $args);
    }
}

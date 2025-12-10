<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

/**
 * ValidateFileUploads Middleware
 * 
 * Additional layer of security for file uploads.
 * Validates file uploads at the HTTP request level before they reach the application logic.
 */
class ValidateFileUploads
{
    /**
     * Dangerous file extensions that should never be allowed
     */
    private const DANGEROUS_EXTENSIONS = [
        'php', 'php3', 'php4', 'php5', 'php7', 'php8', 'phtml', 'pht',
        'exe', 'com', 'bat', 'cmd', 'scr', 'vbs', 'js', 'jar',
        'html', 'htm', 'asp', 'aspx', 'jsp', 'jspx',
        'sh', 'bash', 'zsh', 'ksh', 'csh',
        'app', 'dmg', 'pkg', 'deb', 'rpm',
    ];

    /**
     * Suspicious MIME types that could indicate malicious files
     */
    private const SUSPICIOUS_MIME_TYPES = [
        'application/x-php',
        'application/x-httpd-php',
        'application/x-sh',
        'application/x-msdownload',
        'application/x-executable',
        'text/x-php',
        'text/html',
    ];

    /**
     * Maximum file size in bytes (50MB global limit)
     */
    private const MAX_FILE_SIZE = 50 * 1024 * 1024;

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Only validate if request has files
        if (!$request->hasFile('file') && !$request->hasFile('files')) {
            return $next($request);
        }

        // Get all uploaded files
        $files = [];
        if ($request->hasFile('file')) {
            $files[] = $request->file('file');
        }
        if ($request->hasFile('files')) {
            $files = array_merge($files, $request->file('files'));
        }

        // Validate each file
        foreach ($files as $file) {
            if (!$file || !$file->isValid()) {
                continue;
            }

            // Check file size
            if ($file->getSize() > self::MAX_FILE_SIZE) {
                Log::warning('File upload rejected: exceeds maximum size', [
                    'filename' => $file->getClientOriginalName(),
                    'size' => $file->getSize(),
                    'ip' => $request->ip(),
                ]);

                return response()->json([
                    'error' => 'File exceeds maximum allowed size of 50MB',
                ], 413);
            }

            // Check extension
            $extension = strtolower($file->getClientOriginalExtension());
            if (in_array($extension, self::DANGEROUS_EXTENSIONS)) {
                Log::warning('File upload rejected: dangerous extension', [
                    'filename' => $file->getClientOriginalName(),
                    'extension' => $extension,
                    'ip' => $request->ip(),
                ]);

                return response()->json([
                    'error' => "File type .{$extension} is not allowed for security reasons",
                ], 400);
            }

            // Check MIME type
            $mimeType = $file->getMimeType();
            if (in_array($mimeType, self::SUSPICIOUS_MIME_TYPES)) {
                Log::warning('File upload rejected: suspicious MIME type', [
                    'filename' => $file->getClientOriginalName(),
                    'mime_type' => $mimeType,
                    'ip' => $request->ip(),
                ]);

                return response()->json([
                    'error' => 'File type is not allowed for security reasons',
                ], 400);
            }

            // Check for path traversal in filename
            $originalName = $file->getClientOriginalName();
            if (str_contains($originalName, '..') || 
                str_contains($originalName, '/') || 
                str_contains($originalName, '\\')) {
                
                Log::warning('File upload rejected: path traversal attempt', [
                    'filename' => $originalName,
                    'ip' => $request->ip(),
                ]);

                return response()->json([
                    'error' => 'Invalid filename detected',
                ], 400);
            }

            // Check for null bytes in filename (potential security issue)
            if (str_contains($originalName, "\0")) {
                Log::warning('File upload rejected: null byte in filename', [
                    'filename' => $originalName,
                    'ip' => $request->ip(),
                ]);

                return response()->json([
                    'error' => 'Invalid filename detected',
                ], 400);
            }
        }

        return $next($request);
    }
}

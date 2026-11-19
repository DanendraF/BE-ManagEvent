<?php

use Illuminate\Support\Facades\Route;

// 1. Single Blade Full Portal UI
Route::get('/', function () {
    return view('app');
});

// 2. Interactive Swagger / OpenAPI Documentation
Route::get('/docs', function () {
    return view('docs');
});

// 3. OpenAPI 3.0 JSON Specification Endpoint
Route::get('/api/v1/openapi.json', function () {
    $path = public_path('openapi.json');
    if (! file_exists($path)) {
        abort(404, 'OpenAPI specification file not found.');
    }
    return response(file_get_contents($path), 200, [
        'Content-Type' => 'application/json',
    ]);
});

// 4. Public Storage Asset Serving (Mencegah ketergantungan pada NTFS Symlink di Windows)
Route::get('/storage/{path}', function (string $path) {
    $filePath = storage_path('app/public/' . $path);
    if (! file_exists($filePath)) {
        abort(404, 'File not found');
    }
    $mime = mime_content_type($filePath) ?: 'image/jpeg';
    return response(file_get_contents($filePath), 200, [
        'Content-Type' => $mime,
        'Cache-Control' => 'public, max-age=86400',
    ]);
})->where('path', '.*');


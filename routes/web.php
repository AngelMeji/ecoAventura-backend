<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return response()->json([ 'message' => 'ecoAventura API']);
});

if (app()->environment('local')) {
    Route::get('/storage/{path}', function ($path) {
        $filePath = \Illuminate\Support\Facades\Storage::disk('public')->path($path);
        if (file_exists($filePath)) {
            $mimeType = \Illuminate\Support\Facades\File::mimeType($filePath);
            return response()->file($filePath, [
                'Content-Type' => $mimeType,
                'Cache-Control' => 'public, max-age=86400',
            ]);
        }
        abort(404);
    })->where('path', '.*');
}

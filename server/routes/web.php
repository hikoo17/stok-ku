<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return response()->json([
        'name' => 'Stok Ku API',
        'status' => 'ok',
        'api_version' => 'v1',
        'documentation' => 'Lihat README.md pada akar proyek.',
    ]);
});

<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;

class HomeController extends Controller
{
    public function __invoke(): JsonResponse
    {
        return response()->json([
            'name' => 'Stok Ku API',
            'status' => 'ok',
            'api_version' => 'v1',
            'documentation' => 'Lihat README.md pada akar proyek.',
        ]);
    }
}

<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class SupplierController extends Controller
{
    public function reserve(Request $request)
    {
        // Very naive implementation for demo/testing
        $ref = 'SUP-' . now()->format('YmdHis') . '-' . Str::random(4);

        // Store initial status as delayed for first two checks
        Cache::put("supplier_status_{$ref}", [
            'status' => 'delayed',
            'checks' => 0,
        ], now()->addMinutes(5));

        return response()->json([
            'accepted' => true,
            'ref' => $ref,
        ]);
    }

    /**
     * @param string $ref
     * @return JsonResponse
     */
    public function status(string $ref)
    {
        $hash = crc32($ref);
        $rnd  = $hash % 100;

        $status = match (true) {
            $rnd <= 40 => 'ok',
            $rnd <= 60 => 'fail',
            default    => 'delayed',
        };

        return response()->json([
            'ref'    => $ref,
            'status' => $status,
        ]);
    }
}

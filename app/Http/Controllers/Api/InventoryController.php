<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\InventoryMovement;

class InventoryController extends Controller
{
    public function movements(string $sku)
    {
        $movements = InventoryMovement::where('sku', $sku)
            ->orderBy('created_at')
            ->get();

        return response()->json($movements);
    }
}

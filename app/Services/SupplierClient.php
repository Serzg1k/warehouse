<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class SupplierClient
{
    public function reserve(string $sku, int $qty): array
    {
        // Can be replaced with Http::fake() in tests
        $response = Http::post(url('/api/supplier/reserve'), [
            'sku' => $sku,
            'qty' => $qty,
        ]);

        // Basic error handling
        if (! $response->ok()) {
            return [
                'accepted' => false,
                'ref' => null,
                'error' => 'http_error',
            ];
        }

        return $response->json();
    }

    public function status(string $ref): ?string
    {
        $response = Http::get(url("/api/supplier/status/{$ref}"));

        if (! $response->ok()) {
            return 'fail';
        }

        return $response->json('status');
    }
}

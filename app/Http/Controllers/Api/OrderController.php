<?php

namespace App\Http\Controllers\Api;

use App\Events\OrderCreated;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreOrderRequest;
use App\Repositories\OrderRepository;
use Illuminate\Http\JsonResponse;

class OrderController extends Controller
{
    public function __construct(
        private readonly OrderRepository $orders,
    ) {
    }

    public function store(StoreOrderRequest $request): JsonResponse
    {
        $data = $request->validated();
        $order = $this->orders->create($data);
        OrderCreated::dispatch($order);

        return response()->json($order, 201);
    }

    public function show(int $id): JsonResponse
    {
        $order = $this->orders->findByIdOrFail($id);

        return response()->json($order);
    }

    public function index(): JsonResponse
    {
        // Simple demo: return all orders (in real app use pagination)
        $orders = $this->orders->all();

        return response()->json($orders);
    }
}

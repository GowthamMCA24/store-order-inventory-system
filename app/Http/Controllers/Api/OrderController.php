<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreOrderRequest;
use App\Services\OrderService;
use Illuminate\Http\JsonResponse;
use App\Models\Customer;
use Illuminate\Http\Request;
use App\Models\Product;

class OrderController extends Controller
{
    public function store( StoreOrderRequest $request, OrderService $orderService): JsonResponse {

        $order = $orderService->createOrder(
            $request->validated()
        );

        return response()->json([
            'success' => true,
            'message' => 'Order created successfully',
            'data' => $order,
        ], 201);
    }

    public function customerOrders(Request $request): JsonResponse
    {
        $request->validate([
            'email' => ['required', 'email', 'exists:customers,email'],
        ]);

        $customer = Customer::where('email', $request->email)
            ->with('orders.items.product')
            ->first();

        return response()->json([
            'success' => true,
            'message' => 'Customer orders retrieved successfully',
            'data' => $customer,
        ]);
    }

    public function lowStock(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'threshold' => ['sometimes', 'integer', 'min:0'],
        ]);

        $threshold = $validated['threshold'] ?? 10;

        $products = Product::where('stock', '<', $threshold)
            ->orderBy('stock')
            ->get();

        return response()->json([
            'success' => true,
            'threshold' => $threshold,
            'count' => $products->count(),
            'data' => $products,
        ]);
    }
}

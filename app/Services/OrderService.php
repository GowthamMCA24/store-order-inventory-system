<?php

namespace App\Services;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use App\Jobs\SendOrderConfirmationJob;

class OrderService
{
    public function createOrder(array $data): Order
    {
        return DB::transaction(function () use ($data) {

            // 1. Find or create customer
            $customer = Customer::firstOrCreate(
                ['email' => $data['customer_email']],
                ['name' => $data['customer_name']]
            );

            $subtotal = 0;
            $taxAmount = 0;
            $orderItems = [];

            // 2. Lock and check each product
            foreach ($data['items'] as $item) {

                $product = Product::where('id', $item['product_id'])
                    ->lockForUpdate()
                    ->firstOrFail();

                $quantity = $item['quantity'];

                if ($product->stock < $quantity) {
                    throw ValidationException::withMessages([
                        'items' => [
                            "Insufficient stock for {$product->name}. Available: {$product->stock}"
                        ]
                    ]);
                }

                // 3. Calculate product amount
                $itemSubtotal = $product->price * $quantity;

                $itemTax = $itemSubtotal *
                    ($product->tax_percentage / 100);

                $itemTotal = $itemSubtotal + $itemTax;

                $subtotal += $itemSubtotal;
                $taxAmount += $itemTax;

                // 4. Prepare order item data
                $orderItems[] = [
                    'product_id' => $product->id,
                    'quantity' => $quantity,
                    'unit_price' => $product->price,
                    'tax_percentage' => $product->tax_percentage,
                    'subtotal' => $itemSubtotal,
                    'tax_amount' => $itemTax,
                    'total' => $itemTotal,
                ];

                // 5. Deduct stock
                $product->decrement('stock', $quantity);
            }

            // 6. Create order
            $order = Order::create([
                'customer_id' => $customer->id,
                'subtotal' => $subtotal,
                'tax_amount' => $taxAmount,
                'grand_total' => $subtotal + $taxAmount,
            ]);

            // 7. Save order items
            $order->items()->createMany($orderItems);

            $order->load('customer', 'items.product');

            SendOrderConfirmationJob::dispatch($order)->afterCommit();

            return $order;
        });
    }
}

<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_can_create_order(): void
    {
        $customer = Customer::factory()->create();

        $product = Product::factory()->create([
            'price' => 100,
            'tax_percentage' => 5,
            'stock' => 10,
        ]);

        $response = $this->postJson('/api/orders', [
            'customer_name' => $customer->name,
            'customer_email' => $customer->email,
            'items' => [
                [
                    'product_id' => $product->id,
                    'quantity' => 2,
                ],
            ],
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('success', true);

        $this->assertDatabaseHas('orders', [
            'customer_id' => $customer->id,
            'subtotal' => 200,
            'tax_amount' => 10,
            'grand_total' => 210,
        ]);

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'stock' => 8,
        ]);
    }

    public function test_order_fails_when_stock_is_insufficient(): void
    {
        $customer = Customer::factory()->create();

        $product = Product::factory()->create([
            'price' => 100,
            'tax_percentage' => 5,
            'stock' => 2,
        ]);

        $response = $this->postJson('/api/orders', [
            'customer_name' => $customer->name,
            'customer_email' => $customer->email,
            'items' => [
                [
                    'product_id' => $product->id,
                    'quantity' => 5,
                ],
            ],
        ]);

        $response->assertStatus(422);

        $this->assertDatabaseMissing('orders', [
            'customer_id' => $customer->id,
        ]);

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'stock' => 2,
        ]);
    }
}

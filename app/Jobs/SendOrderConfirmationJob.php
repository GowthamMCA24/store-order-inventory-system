<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use App\Models\Order;

class SendOrderConfirmationJob implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public Order $order
    ) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
         Log::info('Order confirmation email simulated', [
            'order_id' => $this->order->id,
            'customer_name' => $this->order->customer->name,
            'customer_email' => $this->order->customer->email,
            'grand_total' => $this->order->grand_total,
        ]);
    }
}

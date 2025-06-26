<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class updateOrderSerialCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:update-order-serial-command';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting to update order serials...');

        $orders = \App\Models\ShopifyOrder::whereNull('shipping_serial')
//            ->where('shipping_status', \App\Enum\ShippingStatusEnum::READY_TO_SHIP->value)
            ->get();

        foreach ($orders as $order) {
            try {
                $serial = $order->logs()
                    ->where('action', 'SendShipment')
                    ->latest()
                    ->value('shareex_serial_number');
                if ($serial) {
                    $order->update(['shipping_serial' => $serial]);
                    $this->info("Updated order ID {$order->id} with serial {$serial}");
                } else {
                    $this->warn("No serial found for order ID {$order->id}");
                }
            } catch (\Exception $e) {
                $this->error("Error updating order ID {$order->id}: " . $e->getMessage());
            }
        }

        $this->info('Order serial update completed.');
    }
}

<?php

namespace App\Console\Commands;

use App\Models\ShareexCredential;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class RegisterShopifyWebhooks extends Command
{
    protected $signature = 'shopify:register-webhooks';

    protected $description = 'Register Shopify webhooks for a specific store';

    public function handle()
    {
        $shop = $this->ask('Enter the Shopify store domain (e.g. mystore.myshopify.com)');
        $accessToken = $this->ask('Enter the Shopify access token');
        $shareexUsername = $this->ask('Enter the ShareEx API username');
        $shareexPassword = $this->ask('Enter the ShareEx API password');



        $email = 'shop@' . $shop; // For example, 'shop@mystore.myshopify.com

        $user = User::where('email', $email)->first();
        if (!$user) {
            $user = User::create([
                'name' => $shop,
                'email' => $email,
                'password' => $accessToken,
            ]);
        }

        $dataToUpdate = [
            "base_url" =>'https://shareex.delivery',
            "api_username" => $shareexUsername,
            'api_password' => $shareexPassword
        ];


        ShareexCredential::updateOrCreate(
            ["shop_id" => $user->id], // Using user ID as shop_id
            $dataToUpdate
        );


//        $webhooks = [
//            [
//                'topic' => 'app/uninstalled',
//                'address' => config('app.url') . '/webhook/app-uninstalled',
//                'format' => 'json'
//            ],
//            [
//                'topic' => 'fulfillments/create',
//                'address' => config('app.url') . '/webhook/fulfillments-create',
//                'format' => 'json'
//            ],
//            [
//                'topic' => 'fulfillments/update',
//                'address' => config('app.url') . '/webhook/fulfillments-update',
//                'format' => 'json'
//            ]
//        ];
//
//        $successCount = 0;
//
//        foreach ($webhooks as $webhook) {
//            $response = Http::withHeaders([
//                'X-Shopify-Access-Token' => $accessToken,
//                'Content-Type' => 'application/json',
//            ])->post("https://{$shop}/admin/api/2023-07/webhooks.json", [
//                'webhook' => $webhook
//            ]);
//
//            Log::debug('webhook response', ['response' => $response->json()]);
//            if ($response->successful()) {
//                $this->info("Successfully registered {$webhook['topic']} webhook");
//                $successCount++;
//            } else {
//                $this->error("Failed to register {$webhook['topic']} webhook");
//                Log::error("Webhook registration failed", [
//                    'shop' => $shop,
//                    'topic' => $webhook['topic'],
//                    'response' => $response->body()
//                ]);
//            }
//        }
//
//        $this->info("Completed. {$successCount}/" . count($webhooks) . " webhooks registered successfully");

        $this->info('Shopify registered successfully');
    }
}

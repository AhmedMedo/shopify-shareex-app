<?php

namespace App\Console\Commands;

use App\Models\ShareexCredential;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class RegisterShopifyWebhooks extends Command
{
    protected $signature = 'shopify:register-webhooks
                            {shop : The Shopify store domain (e.g. mystore.myshopify.com)}
                            {token : The store access token}
                            {shareex_base_url : The Shareex base URL}
                            {shareex_username : The Shareex API username}
                            {shareex_password : The Shareex API password}';

    protected $description = 'Register Shopify webhooks for a specific store';

    public function handle()
    {
        $shop = $this->argument('shop');
        $accessToken = $this->argument('token');
        $shareexBaseUrl = $this->argument('shareex_base_url');
        $shareexUsername = $this->argument('shareex_username');
        $shareexPassword = $this->argument('shareex_password');


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
            "base_url" => $shareexBaseUrl,
            "api_username" => $shareexUsername,
        ];

        if (!empty($shareexPassword)) {
            $dataToUpdate["api_password"] = $shareexPassword;
        }

        ShareexCredential::updateOrCreate(
            ["shop_id" => $user->id], // Using user ID as shop_id
            $dataToUpdate
        );
        $webhooks = [
            [
                'topic' => 'app/uninstalled',
                'address' => config('app.url') . '/webhook/app-uninstalled',
                'format' => 'json'
            ],
            [
                'topic' => 'fulfillments/create',
                'address' => config('app.url') . '/webhook/fulfillments-create',
                'format' => 'json'
            ],
            [
                'topic' => 'fulfillments/update',
                'address' => config('app.url') . '/webhook/fulfillments-update',
                'format' => 'json'
            ]
        ];

        $successCount = 0;

        foreach ($webhooks as $webhook) {
            $response = Http::withHeaders([
                'X-Shopify-Access-Token' => $accessToken,
                'Content-Type' => 'application/json',
            ])->post("https://{$shop}/admin/api/2023-07/webhooks.json", [
                'webhook' => $webhook
            ]);

            Log::debug('webhook response', ['response' => $response->json()]);
            if ($response->successful()) {
                $this->info("Successfully registered {$webhook['topic']} webhook");
                $successCount++;
            } else {
                $this->error("Failed to register {$webhook['topic']} webhook");
                Log::error("Webhook registration failed", [
                    'shop' => $shop,
                    'topic' => $webhook['topic'],
                    'response' => $response->body()
                ]);
            }
        }

        $this->info("Completed. {$successCount}/" . count($webhooks) . " webhooks registered successfully");

        return $successCount === count($webhooks) ? 0 : 1;
    }
}

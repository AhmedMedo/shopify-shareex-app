<?php

namespace App\Console\Commands;

use App\Models\ShippingPartnerCredential;
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
        $storeDomain = $this->ask('Enter the store domain (e.g. kamiz.com)');
        $storeName = $this->ask('Enter the store name (e.g. My Store)');
        $accessToken = $this->ask('Enter the Shopify access token');
        $shippingUsername = $this->ask('Enter the Shipping API username');
        $shippingPassword = $this->ask('Enter the Shipping API password');
        //get choices from shipping_partners config
        $partners = config('shipping_partners.partners');
        $partnerNames = array_keys($partners);
        $partner = $this->choice('Select the shipping partner', $partnerNames, \App\Enum\ShippingPartnerEnum::SHAREEX->value);




        $email = 'shop@' . $shop; // For example, 'shop@mystore.myshopify.com

        $user = User::where('email', $email)->first();
        if (!$user) {
            $user = User::create([
                'name' => $shop,
                'shop_name' => $storeName,
                'shop_domain' => $storeDomain,
                'email' => $email,
                'password' => $accessToken,
            ]);
        }

        $dataToUpdate = [
            'base_url' => config("shipping_partners.partners.".$partner.'.base_url'),
            'api_username' => $shippingUsername,
            'api_password' => $shippingPassword,
            'password' => $shippingPassword,
            'partner' => $partner,
        ];


        ShippingPartnerCredential::updateOrCreate(
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

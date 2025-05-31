<?php

namespace App\Services\Shareex;

use App\Models\ShareexCredential;
use App\Models\User as ShopifyStore;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

// Will be created/renamed later

class ShareexApiService
{
    protected string $apiUrl;
    protected string $username;
    protected string $password;
    protected ?ShopifyStore $shop;

    public function __construct(?ShopifyStore $shop = null)
    {
        Log::debug('ShareexApiService: credentials loaded', ["shop" => $shop]);
        $this->shop = $shop ?: Auth::user();
        $this->loadCredentials();
    }

    protected function loadCredentials()
    {
        if (!$this->shop) {
            Log::error("ShareexApiService: Shop context not available to load credentials.");
            // Set to dummy values or throw exception to prevent API calls without credentials
            $this->apiUrl = "";
            $this->username = "";
            $this->password = "";
            return;
        }

        $credentials = ShareexCredential::where("shop_id", $this->shop->id)->first();
        Log::debug('ShareexApiService: credentials loaded', ["credentials" => $credentials]);
        if ($credentials && $credentials->base_url && $credentials->api_username && $credentials->api_password) {
            $this->apiUrl = $credentials->base_url;
            $this->username = $credentials->api_username;
            $this->password = $credentials->api_password;
//            try {
//                $this->password = Crypt::decryptString($credentials->api_password);
//            } catch (\Illuminate\Contracts\Encryption\DecryptException $e) {
//                Log::error("ShareexApiService: Failed to decrypt password for shop" . $this->shop->id, ["error" => $e->getMessage()]);
//                $this->password = ""; // Set to empty if decryption fails
//            }
        } else {
            Log::warning("ShareexApiService: Credentials not fully configured for shop" . $this->shop->id);
            $this->apiUrl = "";
            $this->username = "";
            $this->password = "";
        }
    }

    // Call this if credentials might have been updated after service instantiation
    public function refreshCredentials(): void
    {
        $this->loadCredentials();
    }

    private function makeRequest(string $endpoint, array $params = [], string $method = "POST"): ?array
    {
        if (empty($this->apiUrl) || empty($this->username) || empty($this->password)) {
            Log::error("Shareex API Request Failed: Credentials not configured or invalid for shop " . ($this->shop ? $this->shop->id : "Unknown"));
            return null;
        }

        $fullUrl = rtrim($this->apiUrl, "/") . "/api/shipments.asmx/" . ltrim($endpoint, "/");

        $requestData = array_merge($params, [
            "uname" => $this->username,
            "upass" => $this->password,
        ]);

        Log::debug('SENDING_REQUEST',[
            "url" => $fullUrl,
            "data" => $requestData
        ]);

        try {
            if ($method === "POST") {
                $response = Http::acceptJson()->post($fullUrl, $requestData);
            } else { // Default to GET
                $response = Http::get($fullUrl, $requestData);
            }

             Log::debug("Shareex API Request Response", ["response" => $response->json(),'r' => $response->body()]);

            if ($response->failed()) {
                Log::error("Shareex API Request Failed", [
                    "shop_id" => $this->shop ? $this->shop->id : null,
                    "url" => $fullUrl,
                    "status" => $response->status(),
                    "response" => $response->body(),
                    "params_sent" => $requestData // Consider redacting password in logs
                ]);
                return null;
            }

            return $response->json();

        } catch (\Exception $e) {
            Log::error("Shareex API Request Exception", [
                "shop_id" => $this->shop ? $this->shop->id : null,
                "url" => $fullUrl,
                "message" => $e->getMessage(),
                "params_sent" => $requestData // Consider redacting password in logs
            ]);
            return null;
        }
    }

    public function sendShipment(array $shipmentData): ?array
    {
        return $this->makeRequest("SendShipment", $shipmentData, "POST");
    }

    public function getShipmentLastStatus(string $serialNumber): ?array
    {
        return $this->makeRequest("GetShipmentLastStatus", ["serial" => $serialNumber], "GET");
    }

    public function getShipmentHistory(string $serialNumber): ?array
    {
        return $this->makeRequest("GetShipmentHistory", ["serial" => $serialNumber], "GET");
    }
}

<?php

namespace App\Services\Shareex;

use App\Models\ShareexCredential; // Will be created/renamed later
use App\Models\User as ShopifyStore;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ShareexApiService
{
    protected string $apiUrl;
    protected string $username;
    protected string $password;
    protected ?ShopifyStore $shop;

    public function __construct(?ShopifyStore $shop = null)
    {
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

        if ($credentials && $credentials->base_url && $credentials->api_username && $credentials->api_password) {
            $this->apiUrl = $credentials->base_url;
            $this->username = $credentials->api_username;
            try {
                $this->password = Crypt::decryptString($credentials->api_password);
            } catch (\Illuminate\Contracts\Encryption\DecryptException $e) {
                Log::error("ShareexApiService: Failed to decrypt password for shop" . $this->shop->id, ["error" => $e->getMessage()]);
                $this->password = ""; // Set to empty if decryption fails
            }
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

        $fullUrl = rtrim($this->apiUrl, "/") . "/" . ltrim($endpoint, "/");

        $requestData = array_merge($params, [
            "uname" => $this->username,
            "upass" => $this->password,
        ]);

        try {
            if ($method === "POST") {
                $response = Http::asForm()->post($fullUrl, $requestData);
            } else { // Default to GET
                $response = Http::get($fullUrl, $requestData);
            }

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

            $decodedResponse = $response->json();
            if ($decodedResponse === null && $response->successful()) {
                return ["raw_response" => $response->body()];
            }
            return $decodedResponse;

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
        return $this->makeRequest("SendShipment.php", $shipmentData, "POST");
    }

    public function getShipmentLastStatus(string $serialNumber): ?array
    {
        return $this->makeRequest("GetShipmentLastStatus.php", ["serial" => $serialNumber], "GET");
    }

    public function getShipmentHistory(string $serialNumber): ?array
    {
        return $this->makeRequest("GetShipmentHistory.php", ["serial" => $serialNumber], "GET");
    }
}

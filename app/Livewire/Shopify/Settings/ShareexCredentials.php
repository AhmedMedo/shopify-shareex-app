<?php

namespace App\Livewire\Shopify\Settings;

use App\Models\ShippingPartnerCredential; // Corrected Model
use App\Models\User as ShopifyStore;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Livewire\Component;

class ShareexCredentials extends Component // Corrected Class Name
{
    public int $shopId = 0;
    public string $baseUrl = "";
    public string $apiUsername = "";
    public string $apiPassword = "";
    public bool $credentialsExist = false;
    public string $successMessage = "";

    protected ShopifyStore $shop;

    public function mount()
    {

        $this->shopId = Auth::user()->id;
        $credentials = ShippingPartnerCredential::where("shop_id", $this->shopId)->first();

        if ($credentials) {
            $this->baseUrl = $credentials->base_url ?? ""; // Accessor will decrypt
            $this->apiUsername = $credentials->api_username ?? ""; // Accessor will decrypt
            // Do not load the actual password into the form for security, only indicate it exists if it was ever set.
            // The ShippingPartnerCredential model returns null from accessor if password is not set or decryption fails.
            $this->credentialsExist = (bool)$credentials->api_password; // Check if password was ever set (even if it is null now after decryption failure)
        }
    }

    public function saveCredentials()
    {
        $this->validate([
            "baseUrl" => "required|url|max:255",
            "apiUsername" => "required|string|max:255",
            "apiPassword" => $this->credentialsExist ? "nullable|string|min:6" : "required|string|min:6",
        ]);

        try {
            $shop = ShopifyStore::find($this->shopId);
            $dataToUpdate = [
                "base_url" => $this->baseUrl, // Mutator will encrypt
                "api_username" => $this->apiUsername, // Mutator will encrypt
            ];

            if (!empty($this->apiPassword)) {
                // Mutator in ShippingPartnerCredential model will handle encryption
                $dataToUpdate["api_password"] = $this->apiPassword;
            }

            ShippingPartnerCredential::updateOrCreate(
                ["shop_id" => $shop->id],
                $dataToUpdate
            );

            $this->credentialsExist = true; // Assume they exist after a successful save
            $this->apiPassword = ""; // Clear password field after saving
            $this->successMessage = "Shareex API credentials saved successfully!";
            Log::info("Shareex credentials updated for shop: " . $shop->name);

            $this->dispatch("show-toast", ["message" => "Credentials saved!", "type" => "success"]);

        } catch (\Exception $e) {
            Log::error("Error saving Shareex credentials for shop: " . $shop->name . " - " . $e->getMessage());
            $this->addError("general", "Failed to save credentials. Please try again. Error: " . $e->getMessage());
            $this->dispatch("show-toast", ["message" => "Error saving credentials.", "type" => "error"]);
        }
    }

    public function render()
    {
        return view("livewire.shopify.settings.shareex-credentials"); // Corrected view path
    }
}

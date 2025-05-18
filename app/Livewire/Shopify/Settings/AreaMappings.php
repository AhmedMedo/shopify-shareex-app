<?php

namespace App\Livewire\Shopify\Settings;

use App\Models\AreaMapping;
use App\Models\User as ShopifyStore;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Livewire\Component;
use Livewire\WithPagination;

class AreaMappings extends Component
{
    use WithPagination;

    public string $shopifyZoneName = "";
    public string $shopifyCityProvince = "";
    public string $mubasherAreaName = "";
    public ?int $editingId = null;
    public bool $showModal = false;
    public string $successMessage = "";

    protected ShopifyStore $shop;

    protected function rules()
    {
        return [
            "shopifyZoneName" => "required|string|max:255",
            "shopifyCityProvince" => "nullable|string|max:255",
            "mubasherAreaName" => "required|string|max:255",
        ];
    }

    public function mount()
    {
        $this->shop = Auth::user();
    }

    public function createOrUpdateAreaMapping()
    {
        $this->validate();

        try {
            AreaMapping::updateOrCreate(
                ["id" => $this->editingId, "shop_id" => $this->shop->id],
                [
                    "shop_id" => $this->shop->id,
                    "shopify_zone_name" => $this->shopifyZoneName,
                    "shopify_city_province" => $this->shopifyCityProvince ?: null, // Ensure null if empty
                    "mubasher_area_name" => $this->mubasherAreaName,
                ]
            );

            $this->successMessage = $this->editingId ? "Area mapping updated successfully!" : "Area mapping created successfully!";
            Log::info($this->successMessage . " for shop: " . $this->shop->name);
            $this->dispatch("show-toast", ["message" => $this->successMessage, "type" => "success"]);
            $this->closeModal();
        } catch (\Illuminate\Database\QueryException $e) {
            if ($e->errorInfo[1] == 1062) { // Duplicate entry
                $this->addError("mubasherAreaName", "This combination of Shopify Zone/City/Province and Mubasher Area already exists.");
            } else {
                Log::error("Error saving area mapping for shop: " . $this->shop->name . " - " . $e->getMessage());
                $this->addError("general", "Failed to save area mapping. Please try again.");
            }
            $this->dispatch("show-toast", ["message" => "Error saving area mapping.", "type" => "error"]);
        } catch (\Exception $e) {
            Log::error("Error saving area mapping for shop: " . $this->shop->name . " - " . $e->getMessage());
            $this->addError("general", "Failed to save area mapping. Please try again.");
            $this->dispatch("show-toast", ["message" => "Error saving area mapping.", "type" => "error"]);
        }
    }

    public function openModal($id = null)
    {
        $this->resetValidation();
        $this->resetInputFields();
        $this->successMessage = "";
        if ($id) {
            $mapping = AreaMapping::where("shop_id", $this->shop->id)->findOrFail($id);
            $this->editingId = $mapping->id;
            $this->shopifyZoneName = $mapping->shopify_zone_name;
            $this->shopifyCityProvince = $mapping->shopify_city_province ?? "";
            $this->mubasherAreaName = $mapping->mubasher_area_name;
        }
        $this->showModal = true;
    }

    public function closeModal()
    {
        $this->showModal = false;
        $this->resetInputFields();
        $this->resetValidation();
        $this->editingId = null;
    }

    private function resetInputFields()
    {
        $this->shopifyZoneName = "";
        $this->shopifyCityProvince = "";
        $this->mubasherAreaName = "";
        $this->successMessage = "";
    }

    public function deleteAreaMapping($id)
    {
        try {
            $mapping = AreaMapping::where("shop_id", $this->shop->id)->findOrFail($id);
            $mapping->delete();
            $this->successMessage = "Area mapping deleted successfully!";
            Log::info($this->successMessage . " for shop: " . $this->shop->name . " (ID: " . $id . ")");
            $this->dispatch("show-toast", ["message" => $this->successMessage, "type" => "success"]);
        } catch (\Exception $e) {
            Log::error("Error deleting area mapping for shop: " . $this->shop->name . " - " . $e->getMessage());
            $this->dispatch("show-toast", ["message" => "Error deleting area mapping.", "type" => "error"]);
        }
    }

    public function render()
    {
        $mappings = AreaMapping::where("shop_id", $this->shop->id)
            ->orderBy("shopify_zone_name")
            ->orderBy("shopify_city_province")
            ->paginate(10);
        return view("livewire.shopify.settings.area-mappings", ["mappings" => $mappings]);
    }
}

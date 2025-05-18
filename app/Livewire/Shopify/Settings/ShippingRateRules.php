<?php

namespace App\Livewire\Shopify\Settings;

use App\Models\ShippingRateRule;
use App\Models\User as ShopifyStore;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Livewire\Component;
use Livewire\WithPagination;

class ShippingRateRules extends Component
{
    use WithPagination;

    public string $name = "";
    public string $destinationAreaPattern = "";
    public ?string $minWeight = null;
    public ?string $maxWeight = null;
    public ?string $minOrderValue = null;
    public ?string $maxOrderValue = null;
    public string $rateAmount = "";
    public string $currency = "SAR"; // Default currency
    public bool $isActive = true;
    public ?int $editingId = null;
    public bool $showModal = false;
    public string $successMessage = "";

    protected ShopifyStore $shop;

    protected function rules()
    {
        return [
            "name" => "required|string|max:255",
            "destinationAreaPattern" => "required|string|max:255",
            "minWeight" => "nullable|numeric|min:0|lte:maxWeight", // lte:maxWeight requires maxWeight to be present if minWeight is
            "maxWeight" => "nullable|numeric|min:0|gte:minWeight", // gte:minWeight requires minWeight to be present if maxWeight is
            "minOrderValue" => "nullable|numeric|min:0|lte:maxOrderValue",
            "maxOrderValue" => "nullable|numeric|min:0|gte:minOrderValue",
            "rateAmount" => "required|numeric|min:0",
            "currency" => "required|string|size:3",
            "isActive" => "boolean",
        ];
    }

    protected $validationAttributes = [
        'destinationAreaPattern' => 'Destination/Area Pattern',
        'minWeight' => 'Min Weight (kg)',
        'maxWeight' => 'Max Weight (kg)',
        'minOrderValue' => 'Min Order Value',
        'maxOrderValue' => 'Max Order Value',
        'rateAmount' => 'Rate Amount',
    ];

    public function mount()
    {
        $this->shop = Auth::user();
    }

    public function createOrUpdateShippingRateRule()
    {
        $this->validate();

        try {
            ShippingRateRule::updateOrCreate(
                ['id' => $this->editingId, 'shop_id' => $this->shop->id],
                [
                    'shop_id' => $this->shop->id,
                    'name' => $this->name,
                    'destination_area_pattern' => $this->destinationAreaPattern,
                    'min_weight' => $this->minWeight ?: null,
                    'max_weight' => $this->maxWeight ?: null,
                    'min_order_value' => $this->minOrderValue ?: null,
                    'max_order_value' => $this->maxOrderValue ?: null,
                    'rate_amount' => $this->rateAmount,
                    'currency' => $this->currency,
                    'is_active' => $this->isActive,
                ]
            );

            $this->successMessage = $this->editingId ? "Shipping rate rule updated successfully!" : "Shipping rate rule created successfully!";
            Log::info($this->successMessage . " for shop: " . $this->shop->name);
            $this->dispatch("show-toast", ["message" => $this->successMessage, "type" => "success"]);
            $this->closeModal();
        } catch (\Exception $e) {
            Log::error("Error saving shipping rate rule for shop: " . $this->shop->name . " - " . $e->getMessage());
            $this->addError("general", "Failed to save shipping rate rule. Please try again.");
            $this->dispatch("show-toast", ["message" => "Error saving shipping rate rule.", "type" => "error"]);
        }
    }

    public function openModal($id = null)
    {
        $this->resetValidation();
        $this->resetInputFields();
        $this->successMessage = "";
        if ($id) {
            $rule = ShippingRateRule::where("shop_id", $this->shop->id)->findOrFail($id);
            $this->editingId = $rule->id;
            $this->name = $rule->name;
            $this->destinationAreaPattern = $rule->destination_area_pattern;
            $this->minWeight = $rule->min_weight;
            $this->maxWeight = $rule->max_weight;
            $this->minOrderValue = $rule->min_order_value;
            $this->maxOrderValue = $rule->max_order_value;
            $this->rateAmount = $rule->rate_amount;
            $this->currency = $rule->currency;
            $this->isActive = $rule->is_active;
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
        $this->name = "";
        $this->destinationAreaPattern = "";
        $this->minWeight = null;
        $this->maxWeight = null;
        $this->minOrderValue = null;
        $this->maxOrderValue = null;
        $this->rateAmount = "";
        $this->currency = "SAR";
        $this->isActive = true;
        $this->successMessage = "";
    }

    public function deleteShippingRateRule($id)
    {
        try {
            $rule = ShippingRateRule::where("shop_id", $this->shop->id)->findOrFail($id);
            $rule->delete();
            $this->successMessage = "Shipping rate rule deleted successfully!";
            Log::info($this->successMessage . " for shop: " . $this->shop->name . " (ID: " . $id . ")");
            $this->dispatch("show-toast", ["message" => $this->successMessage, "type" => "success"]);
        } catch (\Exception $e) {
            Log::error("Error deleting shipping rate rule for shop: " . $this->shop->name . " - " . $e->getMessage());
            $this->dispatch("show-toast", ["message" => "Error deleting shipping rate rule.", "type" => "error"]);
        }
    }

    public function render()
    {
        $rules = ShippingRateRule::where("shop_id", $this->shop->id)
            ->orderBy("name")
            ->paginate(10);
        return view("livewire.shopify.settings.shipping-rate-rules", ["rules" => $rules]);
    }
}

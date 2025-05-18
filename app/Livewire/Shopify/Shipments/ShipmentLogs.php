<?php

namespace App\Livewire\Shopify\Shipments;

use App\Models\ShipmentLog;
use App\Models\User as ShopifyStore;
use App\Services\Shareex\ShareexApiService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Livewire\Component;
use Livewire\WithPagination;

class ShipmentLogs extends Component
{
    use WithPagination;

    public string $searchTerm = "";
    public string $filterStatus = "all"; // all, success, failed
    public ?string $selectedLogDetails = null;
    public bool $showDetailsModal = false;

    protected ShopifyStore $shop;
    protected ShareexApiService $shareexApiService;

    public function boot(ShareexApiService $shareexApiService)
    {
        $this->shareexApiService = $shareexApiService;
    }

    public function mount()
    {
        $this->shop = Auth::user();
    }

    public function updatedSearchTerm()
    {
        $this->resetPage();
    }

    public function updatedFilterStatus()
    {
        $this->resetPage();
    }

    public function viewDetails($logId)
    {
        $log = ShipmentLog::where("shop_id", $this->shop->id)->findOrFail($logId);
        $this->selectedLogDetails = json_encode([
            "request_payload" => json_decode($log->request_payload, true),
            "response_payload" => json_decode($log->response_payload, true),
        ], JSON_PRETTY_PRINT);
        $this->showDetailsModal = true;
    }

    public function closeModal()
    {
        $this->showDetailsModal = false;
        $this->selectedLogDetails = null;
    }

    public function getShipmentStatusFromMubasher($logId)
    {
        $log = ShipmentLog::where("shop_id", $this->shop->id)->findOrFail($logId);
        if ($log->mubasher_serial_number) {
            try {
                $statusResponse = $this->shareexApiService->getShipmentLastStatus($log->mubasher_serial_number);
                // Log this attempt
                ShipmentLog::create([
                    "shop_id" => $this->shop->id,
                    "shopify_order_id" => $log->shopify_order_id,
                    "mubasher_serial_number" => $log->mubasher_serial_number,
                    "action" => "GetShipmentLastStatus (Manual)",
                    "request_payload" => json_encode(["serial" => $log->mubasher_serial_number]),
                    "response_payload" => json_encode($statusResponse),
                    "status" => $statusResponse ? "success" : "failed",
                    "error_message" => !$statusResponse ? "API request failed or returned null." : null,
                ]);

                if ($statusResponse && isset($statusResponse["raw_response"])) {
                    $this->dispatch("show-toast", ["message" => "Mubasher Status for serial {$log->mubasher_serial_number}: {$statusResponse["raw_response"]}", "type" => "info"]);
                } else {
                    $this->dispatch("show-toast", ["message" => "Could not retrieve status or unexpected response.", "type" => "error"]);
                }
            } catch (\Exception $e) {
                Log::error("Error fetching Mubasher status for serial: " . $log->mubasher_serial_number . " - " . $e->getMessage());
                $this->dispatch("show-toast", ["message" => "Error fetching status from Mubasher.", "type" => "error"]);
            }
        } else {
            $this->dispatch("show-toast", ["message" => "No Mubasher serial number available for this log entry.", "type" => "warning"]);
        }
    }

    public function render()
    {
        $query = ShipmentLog::where("shop_id", $this->shop->id);

        if (!empty($this->searchTerm)) {
            $query->where(function ($q) {
                $q->where("shopify_order_id", "like", "%" . $this->searchTerm . "%")
                    ->orWhere("mubasher_serial_number", "like", "%" . $this->searchTerm . "%");
            });
        }

        if ($this->filterStatus !== "all") {
            $query->where("status", $this->filterStatus);
        }

        $logs = $query->orderBy("created_at", "desc")->paginate(15);

        return view("livewire.shopify.shipments.shipment-logs", ["logs" => $logs]);
    }
}

<?php

namespace App\Http\Controllers\Shopify;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Models\ShippingRateRule;
use App\Models\AreaMapping; // If needed for complex destination matching

class CarrierServiceController extends Controller
{
    /**
     * Calculate and return shipping rates to Shopify.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getRates(Request $request)
    {
        // IMPORTANT: HMAC VERIFICATION IS CRUCIAL HERE.
        // The kyon147/laravel-shopify package should provide middleware for this.
        // For now, we proceed with the logic, assuming HMAC is verified by a middleware on the route.

        Log::info("Shopify Carrier Service Request Received:", $request->all());

        $rates = [];
        $requestData = $request->input("rate");

        if (!$requestData) {
            Log::error("Shopify Carrier Service: Invalid request format.");
            return response()->json(["rates" => []]);
        }

        $destination = $requestData["destination"] ?? null;
        $items = $requestData["items"] ?? [];
        // $currency = $requestData["currency"] ?? "SAR"; // Shopify sends this

        if (!$destination) {
            Log::error("Shopify Carrier Service: Destination not provided.");
            return response()->json(["rates" => []]);
        }

        // Calculate total weight and value from items
        $totalWeightGrams = 0;
        $totalValue = 0;
        foreach ($items as $item) {
            $totalWeightGrams += ($item["grams"] ?? 0) * ($item["quantity"] ?? 1);
            $totalValue += (float)($item["price"] ?? 0) * ($item["quantity"] ?? 1); // Price is in cents
        }
        $totalWeightKg = $totalWeightGrams / 1000;
        $totalOrderValue = $totalValue / 100; // Convert cents to currency unit

        // --- Logic to find matching shipping rates ---
        // This is a simplified example. You might need more complex logic for area_pattern matching.
        // For example, matching country, province, city against patterns in `destination_area_pattern`.
        // And potentially using AreaMapping model if Mubasher areas are different from Shopify zones.

        $matchingRules = ShippingRateRule::where("is_active", true)
            // Crude destination matching - this needs refinement based on how destination_area_pattern is structured
            // For example, if destination_area_pattern is a city name:
            ->where(function ($query) use ($destination) {
                $query->where("destination_area_pattern", $destination["city"])
                      ->orWhere("destination_area_pattern", $destination["province"])
                      ->orWhere("destination_area_pattern", $destination["country"]);
                // Add more sophisticated pattern matching here if destination_area_pattern is like "ZONE_A*"
                // Or if you use AreaMapping to map Shopify destination to Mubasher areas, query AreaMapping first.
            })
            ->where(function ($query) use ($totalWeightKg) {
                $query->whereNull("min_weight")
                      ->orWhere("min_weight", "<=", $totalWeightKg);
            })
            ->where(function ($query) use ($totalWeightKg) {
                $query->whereNull("max_weight")
                      ->orWhere("max_weight", ">=", $totalWeightKg);
            })
            ->where(function ($query) use ($totalOrderValue) {
                $query->whereNull("min_order_value")
                      ->orWhere("min_order_value", "<=", $totalOrderValue);
            })
            ->where(function ($query) use ($totalOrderValue) {
                $query->whereNull("max_order_value")
                      ->orWhere("max_order_value", ">=", $totalOrderValue);
            })
            ->get();

        if ($matchingRules->isEmpty()) {
            Log::info("Shopify Carrier Service: No matching shipping rules found.", [
                "destination" => $destination,
                "total_weight_kg" => $totalWeightKg,
                "total_order_value" => $totalOrderValue
            ]);
        }

        foreach ($matchingRules as $rule) {
            $rates[] = [
                "service_name" => $rule->name, // e.g., "Mubasher Standard Shipping"
                "service_code" => "mubasher-" . str_replace(" ", "-", strtolower($rule->name)), // Unique code
                "total_price" => (int)($rule->rate_amount * 100), // Price in cents
                "currency" => $rule->currency, // e.g., "SAR"
                "description" => "Estimated delivery via Mubasher Shipping.",
                // Optionally add min/max delivery days if available
                // "min_delivery_date" => "2025-05-10 14:48:45 -0400",
                // "max_delivery_date" => "2025-05-12 14:48:45 -0400",
            ];
        }

        Log::info("Shopify Carrier Service: Rates provided.", ["rates" => $rates]);
        return response()->json(["rates" => $rates]);
    }
}


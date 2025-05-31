<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ShippingCityMapperService
{
    protected array $manualMapping;
    protected array $arabicCities;

    public function __construct()
    {
        $this->arabicCities = config('shareex_areas');
        $this->manualMapping = config('shareex_areas_mapped');
    }

    private static function getManualMapping()
    {
        return config('shareex_areas_mapped');
    }

    public static function getManualMappingArray()
    {
        return config('shareex_areas');
    }

    /**
     * Get comprehensive manual mapping including all Arabic cities
     */
    private static function getComprehensiveMapping(): array
    {
        $comprehensiveMapping = self::getManualMapping();

        // Add all Arabic cities as exact matches (for cases where Arabic is used in English fields)
        foreach (self::getManualMappingArray() as $arabicCity) {
            if ($arabicCity !== '-') { // Skip the dash entry
                $comprehensiveMapping[mb_strtolower($arabicCity)] = $arabicCity;
            }
        }

        return $comprehensiveMapping;
    }

    /**
     * Main method to get Shareex city from shipping address
     */
    public function getShareexCity(array $shippingAddress): ?string
    {
        if (empty($shippingAddress)) {
            return null;
        }

        $city = $shippingAddress['city'] ?? '';
        $address1 = $shippingAddress['address1'] ?? '';
        $address2 = $shippingAddress['address2'] ?? '';
        $province = $shippingAddress['province'] ?? '';

        // Combine all address parts for better matching
        $fullAddress = trim($city . ' ' . $address1 . ' ' . $address2 . ' ' . $province);

        // 1. Try manual mapping first
        $mappedCity = $this->tryManualMapping($city);
        if ($mappedCity) {
            $this->logMapping($city, $mappedCity, 'manual');
            return $mappedCity;
        }

        // 2. Try extracting from full address
        $mappedCity = $this->extractCityFromAddress($fullAddress);
        if ($mappedCity) {
            $this->logMapping($fullAddress, $mappedCity, 'extraction');
            return $mappedCity;
        }

        // 3. Try fuzzy matching
        $mappedCity = $this->fuzzyMatch($city);
        if ($mappedCity) {
            $this->logMapping($city, $mappedCity, 'fuzzy');
            return $mappedCity;
        }

        // 4. Fallback to AI
//        $mappedCity = $this->mapCityUsingAI($fullAddress);
//        if ($mappedCity) {
//            $this->logMapping($fullAddress, $mappedCity, 'ai');
//            return $mappedCity;
//        }

        // Log unmapped city for future manual mapping
        $this->logUnmappedCity($fullAddress);

        return null;
    }

    /**
     * Try manual mapping with normalized keys
     */
    private function tryManualMapping(string $city): ?string
    {
        if (empty($city)) {
            return null;
        }

        $normalizedCity = $this->normalizeString($city);
        $comprehensiveMapping = self::getComprehensiveMapping();

        return $comprehensiveMapping[$normalizedCity] ?? null;
    }

    /**
     * Extract city from address by checking for known Arabic cities
     */
    private function extractCityFromAddress(string $fullAddress): ?string
    {
        $normalizedAddress = $this->normalizeString($fullAddress);

        // Check if any Arabic city appears in the address
        foreach ($this->arabicCities as $arabicCity) {
            if (mb_strpos($normalizedAddress, $arabicCity) !== false) {
                return $arabicCity;
            }
        }

        // Check manual mapping keys in the full address
        $comprehensiveMapping = self::getComprehensiveMapping();
        foreach ($comprehensiveMapping as $englishKey => $arabicCity) {
            if (mb_strpos($normalizedAddress, $englishKey) !== false) {
                return $arabicCity;
            }
        }

        return null;
    }

    /**
     * Simple fuzzy matching for similar city names
     */
    private function fuzzyMatch(string $city): ?string
    {
        if (empty($city)) {
            return null;
        }

        $normalizedCity = $this->normalizeString($city);
        $bestMatch = null;
        $highestSimilarity = 0;
        $comprehensiveMapping = self::getComprehensiveMapping();

        foreach ($comprehensiveMapping as $englishKey => $arabicCity) {
            similar_text($normalizedCity, $englishKey, $similarity);

            if ($similarity > 80 && $similarity > $highestSimilarity) {
                $highestSimilarity = $similarity;
                $bestMatch = $arabicCity;
            }
        }

        return $bestMatch;
    }

    /**
     * Use OpenAI to map city intelligently
     */
    private function mapCityUsingAI(string $addressInfo): ?string
    {
        // Check cache first
        $cacheKey = 'city_mapping_' . md5($addressInfo);
        $cached = Cache::get($cacheKey);
        if ($cached) {
            return $cached;
        }

        try {
            $apiKey = config('services.openai.api_key');
            if (!$apiKey) {
                Log::warning('OpenAI API key not configured');
                return null;
            }

            // Split cities into chunks to handle OpenAI token limits
            $cityChunks = array_chunk($this->arabicCities, 100);

            foreach ($cityChunks as $chunk) {
                $arabicCitiesList = implode('، ', $chunk);

                $prompt = "You are helping map Egyptian addresses to Arabic city names.

Given this address information: '{$addressInfo}'

Find the most appropriate Arabic city name from this list: {$arabicCitiesList}

Rules:
1. Return ONLY the Arabic city name that best matches
2. If no good match exists, return 'NO_MATCH'
3. Consider common variations (Maadi = المعادى, New Cairo = القاهره الجديدة, etc.)
4. Look for city names in both English and Arabic within the address
5. Match partial words too (e.g., 'زهراء المعادي' in address should match 'زهراء المعادى')

Response (Arabic city name only):";

                $response = Http::withHeaders([
                    'Authorization' => 'Bearer ' . $apiKey,
                    'Content-Type' => 'application/json',
                ])->timeout(15)->post('https://api.openai.com/v1/chat/completions', [
                    'model' => 'gpt-4o-mini', // More cost-effective than gpt-3.5-turbo
                    'messages' => [
                        [
                            'role' => 'user',
                            'content' => $prompt
                        ]
                    ],
                    'max_tokens' => 50,
                    'temperature' => 0.1,
                ]);

                if ($response->successful()) {
                    $result = $response->json();
                    $mappedCity = trim($result['choices'][0]['message']['content'] ?? '');

                    // Validate the response is in our Arabic cities list
                    if ($mappedCity && $mappedCity !== 'NO_MATCH' && in_array($mappedCity, $this->arabicCities)) {
                        // Cache for 24 hours
                        Cache::put($cacheKey, $mappedCity, now()->addHours(24));
                        return $mappedCity;
                    }
                }

                // Small delay between API calls
                usleep(200000); // 0.2 seconds
            }

        } catch (\Exception $e) {
            Log::error('OpenAI city mapping failed', [
                'address' => $addressInfo,
                'error' => $e->getMessage()
            ]);
        }

        return null;
    }


    /**
     * Normalize string for comparison
     */
    private function normalizeString(string $text): string
    {
        return mb_strtolower(trim($text));
    }

    /**
     * Log successful mappings for analysis
     */
    private function logMapping(string $input, string $output, string $method): void
    {
        Log::info('City mapping success', [
            'input' => $input,
            'output' => $output,
            'method' => $method
        ]);
    }

    /**
     * Log unmapped cities for future manual mapping
     */
    private function logUnmappedCity(string $address): void
    {
        Log::warning('Unmapped city', [
            'address' => $address,
            'timestamp' => now()
        ]);
    }

    /**
     * Update Shopify order with mapped city
     */
    public function updateOrderCity($orderId): bool
    {
        try {
            $order = \App\Models\ShopifyOrder::find($orderId);
            if (!$order || !$order->shipping_address) {
                return false;
            }

            $shippingAddress = json_decode($order->shipping_address, true);
            $shareexCity = $this->getShareexCity($shippingAddress);

            if ($shareexCity) {
                $order->update(['shareex_shipping_city' => $shareexCity]);
                return true;
            }

            return false;
        } catch (\Exception $e) {
            Log::error('Failed to update order city', [
                'order_id' => $orderId,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Batch update all orders without shareex_shipping_city
     */
    public function updateAllOrdersCities(): void
    {
        \App\Models\ShopifyOrder::whereNull('shareex_shipping_city')
            ->chunk(100, function ($orders) {
                foreach ($orders as $order) {
                    $this->updateOrderCity($order->id);

                    // Add small delay to avoid API rate limits
                    usleep(100000); // 0.1 second
                }
            });
    }


}

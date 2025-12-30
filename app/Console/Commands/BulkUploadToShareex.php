<?php

namespace App\Console\Commands;

use App\Models\BulkUploadResult;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\IOFactory;

class BulkUploadToShareex extends Command
{
    protected $signature = 'shareex:bulk-upload {file=bulk_orders.xls}';

    protected $description = 'Upload bulk orders from Excel file to ShareeX';

    private string $apiUrl = 'https://shareex.co';
    private string $username;
    private string $password;

    public function handle(): int
    {
        // Get credentials from user
        $this->username = $this->ask('Enter ShareeX API username');
        $this->password = $this->secret('Enter ShareeX API password');

        if (empty($this->username) || empty($this->password)) {
            $this->error('Username and password are required.');
            return 1;
        }

        // Get file path
        $filename = $this->argument('file');
        $filePath = storage_path('app/' . $filename);

        if (!file_exists($filePath)) {
            $this->error("File not found: {$filePath}");
            return 1;
        }

        $this->info("Reading file: {$filePath}");

        try {
            $spreadsheet = IOFactory::load($filePath);
            $worksheet = $spreadsheet->getActiveSheet();
            $rows = $worksheet->toArray();
        } catch (\Exception $e) {
            $this->error("Failed to read Excel file: " . $e->getMessage());
            return 1;
        }

        if (count($rows) < 2) {
            $this->error('Excel file is empty or has no data rows.');
            return 1;
        }

        // First row is headers
        $headers = array_map('trim', $rows[0]);
        $dataRows = array_slice($rows, 1);

        // Filter out empty rows (rows where all values are null or empty)
        $dataRows = array_filter($dataRows, function ($row) {
            return !$this->isEmptyRow($row);
        });
        $dataRows = array_values($dataRows); // Re-index

        $this->info("Found " . count($dataRows) . " data rows (after filtering empty rows)");
        $this->newLine();

        if (count($dataRows) === 0) {
            $this->error('No valid data rows found in the Excel file.');
            return 1;
        }

        // Create batch ID for this upload
        $batchId = Str::uuid()->toString();
        $this->info("Batch ID: {$batchId}");
        $this->newLine();

        $successCount = 0;
        $failCount = 0;

        foreach ($dataRows as $index => $row) {
            $rowNumber = $index + 2; // Excel row number (1-indexed, skip header)
            $rowData = $this->mapRowToData($headers, $row);

            $this->info("=== Row {$rowNumber} ===");
            $this->line("Customer: {$rowData['name']} | Phone: {$rowData['phone']} | Area: {$rowData['area']}");

            $payload = $this->buildShareexPayload($rowData);

            // Print request
            $this->line("REQUEST:");
            $this->line(json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

            // Save initial record
            $result = BulkUploadResult::create([
                'batch_id' => $batchId,
                'row_number' => $rowNumber,
                'customer_id' => $rowData['customer_id'],
                'customer_name' => $rowData['name'],
                'phone' => $rowData['phone'],
                'address' => $rowData['address'],
                'area' => $rowData['area'],
                'amount' => $rowData['amount'],
                'status' => 'pending',
                'request_payload' => json_encode($payload),
            ]);

            // Send to ShareeX
            $response = $this->sendToShareex($payload);

            // Print response
            $this->line("RESPONSE:");
            $this->line(json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

            if ($response && isset($response['d'])) {
                $decoded = json_decode($response['d'], true);
                $serial = $decoded[0]['serial'] ?? null;

                if ($serial) {
                    $result->update([
                        'status' => 'success',
                        'shareex_serial' => $serial,
                        'response_payload' => json_encode($response),
                    ]);
                    $this->info("✓ SUCCESS - Serial: {$serial}");
                    $successCount++;
                } else {
                    $result->update([
                        'status' => 'failed',
                        'error_message' => 'No serial returned',
                        'response_payload' => json_encode($response),
                    ]);
                    $this->error("✗ FAILED - No serial returned");
                    $failCount++;
                }
            } else {
                $errorMsg = $response['error'] ?? 'API request failed';
                $result->update([
                    'status' => 'failed',
                    'error_message' => $errorMsg,
                    'response_payload' => json_encode($response),
                ]);
                $this->error("✗ FAILED - {$errorMsg}");
                $failCount++;
            }

            $this->newLine();
        }

        $this->newLine();

        // Summary
        $this->info("=== Upload Complete ===");
        $this->info("Batch ID: {$batchId}");
        $this->info("Success: {$successCount}");
        $this->error("Failed: {$failCount}");
        $this->newLine();
        $this->info("Results saved to 'bulk_upload_results' table.");

        return 0;
    }

    private function mapRowToData(array $headers, array $row): array
    {
        $data = [];
        foreach ($headers as $i => $header) {
            $data[$header] = $row[$i] ?? null;
        }

        // Map Excel columns to our structure
        $firstName = trim($data['First Name'] ?? '');
        $lastName = trim($data['Last Name'] ?? '');
        $address1 = trim($data['Default Address Address1'] ?? '');
        $address2 = trim($data['Default Address Address2'] ?? '');

        return [
            'customer_id' => $data['Customer ID'] ?? null,
            'name' => trim("{$firstName} {$lastName}") ?: 'N/A',
            'phone' => $this->cleanPhone($data['Default Address Phone'] ?? $data['Phone'] ?? ''),
            'address' => trim("{$address1} {$address2}") ?: 'N/A',
            'area' => $data['City'] ?? 'القاهرة', // Last column (Arabic city)
            'amount' => (float) ($data['Total Spent'] ?? 0),
            'remarks' => $data['Note'] ?? '',
        ];
    }

    private function cleanPhone(string $phone): string
    {
        // Remove leading apostrophe and spaces
        $phone = ltrim($phone, "'");
        $phone = preg_replace('/\s+/', '', $phone);
        return $phone ?: '0000000000';
    }

    private function buildShareexPayload(array $data): array
    {
        return [
            'clientref' => $data['customer_id'] ?? '',
            'area' => $data['area'],
            'name' => $data['name'],
            'tel' => $data['phone'],
            'address' => $data['address'],
            'remarks' => $data['remarks'] ?? '',
            'pieces' => 1,
            'amount' => $data['amount'],
        ];
    }

    private function sendToShareex(array $payload): ?array
    {
        $fullUrl = rtrim($this->apiUrl, '/') . '/api/shipments.asmx/SendShipment';

        $requestData = array_merge($payload, [
            'uname' => $this->username,
            'upass' => $this->password,
        ]);

        try {
            Log::debug('BulkUpload: Sending to ShareeX', [
                'url' => $fullUrl,
                'payload' => $requestData,
            ]);

            $response = Http::acceptJson()->post($fullUrl, $requestData);

            Log::debug('BulkUpload: ShareeX Response', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            if ($response->failed()) {
                return ['error' => "HTTP {$response->status()}: {$response->body()}"];
            }

            return $response->json();
        } catch (\Exception $e) {
            Log::error('BulkUpload: ShareeX Exception', [
                'message' => $e->getMessage(),
            ]);
            return ['error' => $e->getMessage()];
        }
    }

    private function isEmptyRow(array $row): bool
    {
        foreach ($row as $value) {
            if ($value !== null && $value !== '') {
                return false;
            }
        }
        return true;
    }
}

<?php

namespace App\Livewire\Shopify\Settings;

use Illuminate\Support\Facades\File;
use Livewire\Component;
use Livewire\WithPagination;

class ApplicationLogs extends Component
{
    use WithPagination;

    public string $logContent = "";
    public int $linesToShow = 100; // Show last 100 lines by default
    public string $selectedLogLevel = "all";
    public array $logLevels = ["all", "emergency", "alert", "critical", "error", "warning", "notice", "info", "debug"];

    public function mount()
    {
        $this->loadLogContent();
    }

    public function loadLogContent()
    {
        $logPath = storage_path("logs/laravel.log");
        if (File::exists($logPath)) {
            // Read the entire file
            $lines = file($logPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            $filteredLines = [];

            if ($this->selectedLogLevel !== "all") {
                foreach ($lines as $line) {
                    // Basic log level check - adjust regex if your log format is different
                    if (preg_match("/^\[\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}\] \w+\.{$this->selectedLogLevel}:/i", $line)) {
                        $filteredLines[] = $line;
                    }
                }
            } else {
                $filteredLines = $lines;
            }

            // Get the last N lines from the filtered (or all) lines
            $this->logContent = implode("\n", array_slice($filteredLines, -$this->linesToShow));
        } else {
            $this->logContent = "Log file not found or is empty.";
        }
    }

    public function updatedSelectedLogLevel()
    {
        $this->loadLogContent();
    }

    public function updatedLinesToShow()
    {
        $this->loadLogContent();
    }

    public function clearLogs()
    {
        $logPath = storage_path("logs/laravel.log");
        if (File::exists($logPath)) {
            try {
                File::put($logPath, ""); // Overwrite with empty string
                $this->logContent = "Log file cleared successfully.";
                $this->dispatch("show-toast", ["message" => "Application logs cleared!", "type" => "success"]);
            } catch (\Exception $e) {
                $this->logContent = "Failed to clear log file: " . $e->getMessage();
                $this->dispatch("show-toast", ["message" => "Error clearing logs.", "type" => "error"]);
            }
        } else {
            $this->logContent = "Log file not found.";
        }
    }

    public function render()
    {
        return view("livewire.shopify.settings.application-logs");
    }
}

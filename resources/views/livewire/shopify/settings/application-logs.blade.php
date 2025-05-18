<div>
    <div class="flex flex-col sm:flex-row justify-between items-center mb-6 gap-4">
        <h3 class="text-lg font-medium leading-6 text-gray-900">Application Logs</h3>
        <div class="flex items-center space-x-2 w-full sm:w-auto">
            <label for="logLevel" class="block text-sm font-medium text-gray-700">Log Level:</label>
            <select wire:model="selectedLogLevel" wire:change="loadLogContent" id="logLevel" class="block w-full sm:w-auto shadow-sm sm:text-sm border-gray-300 rounded-md focus:ring-indigo-500 focus:border-indigo-500">
                @foreach($logLevels as $level)
                    <option value="{{ $level }}">{{ ucfirst($level) }}</option>
                @endforeach
            </select>
            <label for="linesToShow" class="block text-sm font-medium text-gray-700">Lines:</label>
            <input wire:model="linesToShow" wire:change="loadLogContent" type="number" id="linesToShow" min="10" max="1000" step="10" class="block w-24 shadow-sm sm:text-sm border-gray-300 rounded-md focus:ring-indigo-500 focus:border-indigo-500">
            <button wire:click="clearLogs" wire:confirm="Are you sure you want to clear the application log file? This action cannot be undone." type="button" class="inline-flex items-center px-3 py-2 border border-red-300 shadow-sm text-sm leading-4 font-medium rounded-md text-red-700 bg-white hover:bg-red-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500">
                Clear Logs
            </button>
        </div>
    </div>

    <div class="bg-gray-800 text-white p-4 rounded-md shadow-md">
        <pre class="overflow-x-auto text-xs whitespace-pre-wrap" style="max-height: 600px;"><code>{{ $logContent }}</code></pre>
    </div>
</div>


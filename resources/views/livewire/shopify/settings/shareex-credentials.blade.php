<div>
    <div class="bg-white shadow sm:rounded-lg">
        <div class="px-4 py-5 sm:p-6">
            <h3 class="text-lg font-medium leading-6 text-gray-900">Shareex API Credentials</h3>
            <div class="mt-2 max-w-xl text-sm text-gray-500">
                <p>Enter your Shareex API Base URL, Username, and Password. These credentials will be used to connect to the Shareex shipping service.</p>
            </div>
            <form wire:submit.prevent="saveCredentials" class="mt-5 space-y-6">
                <input wire:model.defer="shopId" type="hidden" name="shopId" id="shopId" class="block w-full shadow-sm sm:text-sm border-gray-300 rounded-md focus:ring-indigo-500 focus:border-indigo-500" placeholder="e.g., http://api.shareex.com/">

                <div>
                    <label for="baseUrl" class="block text-sm font-medium text-gray-700">API Base URL</label>
                    <div class="mt-1">
                        <input wire:model.defer="baseUrl" type="url" name="baseUrl" id="baseUrl" class="block w-full shadow-sm sm:text-sm border-gray-300 rounded-md focus:ring-indigo-500 focus:border-indigo-500" placeholder="e.g., http://api.shareex.com/">
                    </div>
                    @error("baseUrl") <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="apiUsername" class="block text-sm font-medium text-gray-700">API Username</label>
                    <div class="mt-1">
                        <input wire:model.defer="apiUsername" type="text" name="apiUsername" id="apiUsername" class="block w-full shadow-sm sm:text-sm border-gray-300 rounded-md focus:ring-indigo-500 focus:border-indigo-500" placeholder="Your Shareex Username">
                    </div>
                    @error("apiUsername") <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="apiPassword" class="block text-sm font-medium text-gray-700">API Password</label>
                    <div class="mt-1">
                        <input wire:model.defer="apiPassword" type="password" name="apiPassword" id="apiPassword" class="block w-full shadow-sm sm:text-sm border-gray-300 rounded-md focus:ring-indigo-500 focus:border-indigo-500" placeholder="{{ $credentialsExist ? "Enter new password to change" : "Your Shareex Password" }}">
                    </div>
                    @error("apiPassword") <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
                    @if($credentialsExist)
                        <p class="mt-1 text-xs text-gray-500">Leave blank if you do not want to change the existing password.</p>
                    @endif
                </div>

                @if (session()->has("successMessage") || $successMessage)
                    <div class="rounded-md bg-green-50 p-4">
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <svg class="h-5 w-5 text-green-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                                </svg>
                            </div>
                            <div class="ml-3">
                                <p class="text-sm font-medium text-green-800">{{ session("successMessage") ?: $successMessage }}</p>
                            </div>
                        </div>
                    </div>
                @endif

                @error("general")
                    <div class="rounded-md bg-red-50 p-4">
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <svg class="h-5 w-5 text-red-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-13a1 1 0 10-2 0v4a1 1 0 102 0V5zm-1 9a1 1 0 100-2 1 1 0 000 2z" clip-rule="evenodd" />
                                </svg>
                            </div>
                            <div class="ml-3">
                                <p class="text-sm font-medium text-red-800">{{ $message }}</p>
                            </div>
                        </div>
                    </div>
                @enderror


                <div class="flex justify-end">
                    <button type="submit" class="ml-3 inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                        Save Credentials
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>


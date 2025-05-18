<div>
    <div class="flex justify-between items-center mb-6">
        <h3 class="text-lg font-medium leading-6 text-gray-900">Shipping Rate Rules</h3>
        <button wire:click="openModal()" type="button" class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
            Add New Shipping Rate Rule
        </button>
    </div>

    @if ($successMessage)
        <div class="rounded-md bg-green-50 p-4 mb-4">
            <div class="flex">
                <div class="flex-shrink-0">
                    <svg class="h-5 w-5 text-green-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                    </svg>
                </div>
                <div class="ml-3">
                    <p class="text-sm font-medium text-green-800">{{ $successMessage }}</p>
                </div>
            </div>
        </div>
    @endif

    <!-- Modal -->
    @if($showModal)
    <div class="fixed z-10 inset-0 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true"></div>
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
            <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-2xl sm:w-full">
                <form wire:submit.prevent="createOrUpdateShippingRateRule">
                    <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                        <div class="sm:flex sm:items-start">
                            <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left w-full">
                                <h3 class="text-lg leading-6 font-medium text-gray-900" id="modal-title">
                                    {{ $editingId ? "Edit" : "Add New" }} Shipping Rate Rule
                                </h3>
                                <div class="mt-4 grid grid-cols-1 gap-y-6 gap-x-4 sm:grid-cols-6">
                                    <div class="sm:col-span-6">
                                        <label for="name" class="block text-sm font-medium text-gray-700">Rule Name</label>
                                        <input wire:model.defer="name" type="text" id="name" class="mt-1 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md" placeholder="e.g., Standard Shipping - Riyadh">
                                        @error("name") <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                                    </div>

                                    <div class="sm:col-span-6">
                                        <label for="destinationAreaPattern" class="block text-sm font-medium text-gray-700">Destination/Area Pattern</label>
                                        <input wire:model.defer="destinationAreaPattern" type="text" id="destinationAreaPattern" class="mt-1 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md" placeholder="e.g., Riyadh, Jeddah, ZONE_A*, US-CA">
                                        @error("destinationAreaPattern") <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                                    </div>

                                    <div class="sm:col-span-3">
                                        <label for="minWeight" class="block text-sm font-medium text-gray-700">Min Weight (kg)</label>
                                        <input wire:model.defer="minWeight" type="number" step="0.001" id="minWeight" class="mt-1 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md" placeholder="0.0">
                                        @error("minWeight") <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                                    </div>
                                    <div class="sm:col-span-3">
                                        <label for="maxWeight" class="block text-sm font-medium text-gray-700">Max Weight (kg)</label>
                                        <input wire:model.defer="maxWeight" type="number" step="0.001" id="maxWeight" class="mt-1 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md" placeholder="5.0">
                                        @error("maxWeight") <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                                    </div>

                                    <div class="sm:col-span-3">
                                        <label for="minOrderValue" class="block text-sm font-medium text-gray-700">Min Order Value</label>
                                        <input wire:model.defer="minOrderValue" type="number" step="0.01" id="minOrderValue" class="mt-1 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md" placeholder="0.00">
                                        @error("minOrderValue") <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                                    </div>
                                    <div class="sm:col-span-3">
                                        <label for="maxOrderValue" class="block text-sm font-medium text-gray-700">Max Order Value</label>
                                        <input wire:model.defer="maxOrderValue" type="number" step="0.01" id="maxOrderValue" class="mt-1 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md" placeholder="1000.00">
                                        @error("maxOrderValue") <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                                    </div>

                                    <div class="sm:col-span-3">
                                        <label for="rateAmount" class="block text-sm font-medium text-gray-700">Rate Amount</label>
                                        <input wire:model.defer="rateAmount" type="number" step="0.01" id="rateAmount" class="mt-1 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md" placeholder="25.00">
                                        @error("rateAmount") <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                                    </div>
                                    <div class="sm:col-span-3">
                                        <label for="currency" class="block text-sm font-medium text-gray-700">Currency</label>
                                        <input wire:model.defer="currency" type="text" id="currency" class="mt-1 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md" placeholder="SAR">
                                        @error("currency") <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                                    </div>

                                    <div class="sm:col-span-6">
                                        <div class="flex items-center">
                                            <input wire:model.defer="isActive" id="isActive" name="isActive" type="checkbox" class="h-4 w-4 text-indigo-600 border-gray-300 rounded focus:ring-indigo-500">
                                            <label for="isActive" class="ml-2 block text-sm text-gray-900">Active</label>
                                        </div>
                                        @error("isActive") <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                                    </div>
                                    @error("general") <p class="sm:col-span-6 mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                        <button type="submit" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-indigo-600 text-base font-medium text-white hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:ml-3 sm:w-auto sm:text-sm">
                            {{ $editingId ? "Save Changes" : "Create Rule" }}
                        </button>
                        <button wire:click="closeModal()" type="button" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                            Cancel
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    @endif

    <!-- Table -->
    <div class="flex flex-col mt-6">
        <div class="-my-2 overflow-x-auto sm:-mx-6 lg:-mx-8">
            <div class="py-2 align-middle inline-block min-w-full sm:px-6 lg:px-8">
                <div class="shadow overflow-hidden border-b border-gray-200 sm:rounded-lg">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Destination Pattern</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Weight (Min-Max)</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Order Value (Min-Max)</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Rate</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @forelse ($rules as $rule)
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{{ $rule->name }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $rule->destination_area_pattern }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $rule->min_weight ?: "-" }} - {{ $rule->max_weight ?: "-" }} kg</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $rule->min_order_value ?: "-" }} - {{ $rule->max_order_value ?: "-" }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $rule->rate_amount }} {{ $rule->currency }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        @if($rule->is_active)
                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">Active</span>
                                        @else
                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800">Inactive</span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                        <button wire:click="openModal({{ $rule->id }})" class="text-indigo-600 hover:text-indigo-900">Edit</button>
                                        <button wire:click="deleteShippingRateRule({{ $rule->id }})" wire:confirm="Are you sure you want to delete this shipping rate rule?" class="ml-2 text-red-600 hover:text-red-900">Delete</button>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 text-center">No shipping rate rules found.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    <div class="mt-4">
        {{ $rules->links() }}
    </div>
</div>


@extends('admin.layouts.app')

@section('content')
    <div class="container-fluid">
        <h1 class="mb-4">Shopify Orders</h1>

        <!-- Tab Navigation -->
        <ul class="nav nav-tabs mb-4" id="orderTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <a class="nav-link {{ $activeTab === 'pending' ? 'active' : '' }}" 
                   href="{{ route('admin.home', ['tab' => 'pending']) }}" 
                   role="tab">
                    Pending Orders
                    <span class="badge bg-warning ms-2">{{ \App\Models\ShopifyOrder::where('shop_id', Auth::guard('admin')->user()->shop_id)->where('shipping_status', \App\Enum\ShippingStatusEnum::PENDING->value)->count() }}</span>
                </a>
            </li>
            <li class="nav-item" role="presentation">
                <a class="nav-link {{ $activeTab === 'not_shipped' ? 'active' : '' }}" 
                   href="{{ route('admin.home', ['tab' => 'not_shipped']) }}" 
                   role="tab">
                    Not Shipped
                    <span class="badge bg-info ms-2">{{ \App\Models\ShopifyOrder::where('shop_id', Auth::guard('admin')->user()->shop_id)->whereIn('shipping_status', [\App\Enum\ShippingStatusEnum::READY_TO_SHIP->value, \App\Enum\ShippingStatusEnum::AWAINTING_FOR_SHIPPING_CITY->value])->count() }}</span>
                </a>
            </li>
            <li class="nav-item" role="presentation">
                <a class="nav-link {{ $activeTab === 'shipped' ? 'active' : '' }}" 
                   href="{{ route('admin.home', ['tab' => 'shipped']) }}" 
                   role="tab">
                    Shipped
                    <span class="badge bg-success ms-2">{{ \App\Models\ShopifyOrder::where('shop_id', Auth::guard('admin')->user()->shop_id)->where('shipping_status', \App\Enum\ShippingStatusEnum::SHIPPED->value)->count() }}</span>
                </a>
            </li>
        </ul>

        <div class="card">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped table-hover" id="ordersTable">
                        <thead>
                        <tr>
                            <th>Order ID</th>
                            <th>Customer</th>
                            <th>Phone</th>
                            <th>Email</th>
                            <th>Shipping City</th>
                            <th>ShareEx City</th>
                            <th>ShareEx Serial</th>
                            <th>Shipping Status</th>
                            <th>Actions</th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach($orders as $order)
                            @php
                                $shippingAddress = $order->shipping_address;
                                $latestLog = $order->logs->last();
                            @endphp
                            <tr>
                                <td>
                                    <a href="{{ route('admin.orders.show', $order->id) }}" class="text-decoration-none">
                                        {{ $order->order_number }}
                                    </a>
                                </td>
                                <td>{{ $shippingAddress['first_name'] ?? '' }} {{ $shippingAddress['last_name'] ?? '' }}</td>
                                <td>{{ $shippingAddress['phone'] ?? '' }}</td>
                                <td>{{ $order->email }}</td>
                                <td>{{ $shippingAddress['city'] ?? '' }}</td>
                                <td>
                                    @if($activeTab === 'shipped')
                                        <span class="badge bg-light text-dark">{{ $order->shareex_shipping_city ?: '-' }}</span>
                                    @else
                                        @if(!$order->shareex_shipping_city)
                                            <div class="d-flex align-items-center gap-2">
                                                <select class="form-select form-select-sm city-select"
                                                        data-order-id="{{ $order->id }}"
                                                        style="width: 120px;">
                                                    <option value="">Select city</option>
                                                    @foreach(array_unique(config('shareex_areas')) as $city)
                                                        <option value="{{ $city }}">{{ $city }}</option>
                                                    @endforeach
                                                </select>
                                                <button class="btn btn-sm btn-primary save-city-btn py-1 px-2"
                                                        data-order-id="{{ $order->id }}"
                                                        disabled>
                                                    <i class="bi bi-check-lg"></i>
                                                </button>
                                            </div>
                                        @else
                                            <div class="d-flex align-items-center gap-2">
                                                <span class="badge bg-light text-dark">{{ $order->shareex_shipping_city }}</span>
                                                <button class="btn btn-sm btn-outline-secondary change-city-btn py-1 px-2"
                                                        data-order-id="{{ $order->id }}"
                                                        data-current-city="{{ $order->shareex_shipping_city }}">
                                                    <i class="bi bi-pencil"></i>
                                                </button>
                                            </div>
                                        @endif
                                    @endif
                                </td>
                                <td>
                                    @if($order->shipping_serial)
                                        {{ $order->shipping_serial }}
                                    @else
                                        -
                                    @endif
                                </td>
                                <td>
                                    <span class="badge bg-{{ $order->shipping_status === 'ready_to_ship' ? 'success' : ($order->shipping_status === 'shipped' ? 'info' : ($order->shipping_status === 'pending' ? 'warning' : 'secondary')) }}">
                                        {{ ucfirst(str_replace('_', ' ', $order->shipping_status)) }}
                                    </span>
                                </td>
                                <td>
                                    <div class="btn-group" role="group">
                                        <a href="{{ route('admin.orders.show', $order->id) }}" 
                                           class="btn btn-sm btn-outline-primary" 
                                           title="View Details">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                        
                                        @if($activeTab === 'not_shipped')
                                            @if($order->shipping_status === 'ready_to_ship' && $order->shareex_shipping_city)
                                                <form action="{{ route('admin.orders.update-status', $order->id) }}" method="POST" class="d-inline">
                                                    @csrf
                                                    <input type="hidden" name="shipping_status" value="shipped">
                                                    <button type="submit" class="btn btn-sm btn-success" title="Mark as Shipped">
                                                        <i class="bi bi-truck"></i>
                                                    </button>
                                                </form>
                                            @elseif($order->shipping_status === 'awaiting_for_shipping_city')
                                                <button type="button" class="btn btn-sm btn-secondary" disabled data-bs-toggle="tooltip" data-bs-placement="top" title="Set city first">
                                                    <i class="bi bi-info-circle"></i>
                                                </button>
                                            @endif
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection
@push('scripts')
    <script>
        $(document).ready(function() {
            // Initialize DataTable
            var table = $('#ordersTable').DataTable({
                responsive: true,
                pageLength: 25,
                lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, "All"]],
                order: [[0, 'desc']], // Sort by Order ID descending
                columnDefs: [
                    {
                        targets: -1, // Actions column
                        orderable: false,
                        searchable: false
                    },
                    {
                        targets: [2, 3, 5, 6], // Phone, Email, ShareEx City, ShareEx Serial columns
                        orderable: false
                    }
                ],
                language: {
                    search: "Search orders:",
                    lengthMenu: "Show _MENU_ orders per page",
                    info: "Showing _START_ to _END_ of _TOTAL_ orders",
                    infoEmpty: "Showing 0 to 0 of 0 orders",
                    infoFiltered: "(filtered from _MAX_ total orders)",
                    paginate: {
                        first: "First",
                        last: "Last",
                        next: "Next",
                        previous: "Previous"
                    }
                },
                dom: '<"row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6"f>>' +
                     '<"row"<"col-sm-12"tr>>' +
                     '<"row"<"col-sm-12 col-md-5"i><"col-sm-12 col-md-7"p>>'
            });

            // Enable Bootstrap tooltips
            var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
            tooltipTriggerList.map(function (tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl);
            });

            // Enable save button when city is selected
            $(document).on('change', '.city-select', function() {
                const orderId = $(this).data('order-id');
                const saveBtn = $(`.save-city-btn[data-order-id="${orderId}"]`);
                saveBtn.prop('disabled', $(this).val() === '');
            });

            // Save city selection
            $(document).on('click', '.save-city-btn', async function() {
                const btn = $(this);
                const orderId = btn.data('order-id');
                const select = $(`.city-select[data-order-id="${orderId}"]`);
                const city = select.val();

                if (!city) return;

                btn.prop('disabled', true);
                btn.html('<span class="spinner-border spinner-border-sm" role="status"></span>');

                try {
                    const response = await fetch(`/admin/orders/${orderId}/update-city`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                            'X-Requested-With': 'XMLHttpRequest'
                        },
                        body: JSON.stringify({
                            shareex_shipping_city: city
                        })
                    });

                    if (response.ok) {
                        location.reload();
                    } else {
                        alert('Failed to update city');
                        btn.html('<i class="bi bi-check-lg"></i>');
                        btn.prop('disabled', false);
                    }
                } catch (error) {
                    console.error('Error:', error);
                    alert('An error occurred');
                    btn.html('<i class="bi bi-check-lg"></i>');
                    btn.prop('disabled', false);
                }
            });

            // Change city button
            $(document).on('click', '.change-city-btn', function() {
                const btn = $(this);
                const orderId = btn.data('order-id');
                const currentCity = btn.data('current-city');
                const td = btn.closest('td');

                td.html(`
                    <div class="d-flex align-items-center gap-2">
                        <select class="form-select form-select-sm city-select"
                                data-order-id="${orderId}"
                                style="width: 120px;">
                            <option value="">Select city</option>
                            @foreach(array_unique(config('shareex_areas')) as $city)
                                <option value="{{ $city }}" ${currentCity === '{{ $city }}' ? 'selected' : ''}>
                                    {{ $city }}
                                </option>
                            @endforeach
                        </select>
                        <button class="btn btn-sm btn-primary save-city-btn py-1 px-2"
                                data-order-id="${orderId}">
                            <i class="bi bi-check-lg"></i>
                        </button>
                        <button class="btn btn-sm btn-outline-danger cancel-change-btn py-1 px-2"
                                data-order-id="${orderId}"
                                data-current-city="${currentCity}">
                            <i class="bi bi-x-lg"></i>
                        </button>
                    </div>
                `);
            });

            // Cancel change
            $(document).on('click', '.cancel-change-btn', function() {
                const btn = $(this);
                const orderId = btn.data('order-id');
                const currentCity = btn.data('current-city');
                const td = btn.closest('td');

                td.html(`
                    <div class="d-flex align-items-center gap-2">
                        <span class="badge bg-light text-dark">${currentCity}</span>
                        <button class="btn btn-sm btn-outline-secondary change-city-btn py-1 px-2"
                                data-order-id="${orderId}"
                                data-current-city="${currentCity}">
                            <i class="bi bi-pencil"></i>
                        </button>
                    </div>
                `);
            });

            // Handle form submissions within DataTable
            $(document).on('submit', 'form', function(e) {
                const form = $(this);
                const submitBtn = form.find('button[type="submit"]');
                
                // Show loading state
                submitBtn.prop('disabled', true);
                submitBtn.html('<span class="spinner-border spinner-border-sm" role="status"></span>');
                
                // Re-enable after a short delay to allow form submission
                setTimeout(() => {
                    submitBtn.prop('disabled', false);
                    submitBtn.html(submitBtn.data('original-text') || submitBtn.html());
                }, 2000);
            });
        });
    </script>
@endpush

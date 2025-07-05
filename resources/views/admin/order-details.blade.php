@extends('admin.layouts.app')

@section('content')
    <div class="container-fluid">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1>Order Details - #{{ $order->order_number }}</h1>
            <a href="{{ route('admin.home', ['tab' => 'pending']) }}" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left"></i> Back to Orders
            </a>
        </div>

        <div class="row">
            <!-- Order Information -->
            <div class="col-md-6">
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Order Information</h5>
                    </div>
                    <div class="card-body">
                        <table class="table table-borderless">
                            <tr>
                                <td><strong>Order ID:</strong></td>
                                <td>{{ $order->order_number }}</td>
                            </tr>
                            <tr>
                                <td><strong>Shopify Order ID:</strong></td>
                                <td>{{ $order->shopify_order_id }}</td>
                            </tr>
                            <tr>
                                <td><strong>Email:</strong></td>
                                <td>{{ $order->email }}</td>
                            </tr>
                            <tr>
                                <td><strong>Created At:</strong></td>
                                <td>{{ $order->created_at->format('Y-m-d H:i:s') }}</td>
                            </tr>
                            <tr>
                                <td><strong>Processed At:</strong></td>
                                <td>{{ $order->processed_at ? $order->processed_at->format('Y-m-d H:i:s') : 'Not processed' }}</td>
                            </tr>
                            <tr>
                                <td><strong>Shipping Status:</strong></td>
                                <td>
                                    <span class="badge bg-{{ $order->shipping_status === 'ready_to_ship' ? 'success' : ($order->shipping_status === 'shipped' ? 'info' : ($order->shipping_status === 'pending' ? 'warning' : 'secondary')) }}">
                                        {{ ucfirst(str_replace('_', ' ', $order->shipping_status)) }}
                                    </span>
                                </td>
                            </tr>
                            <tr>
                                <td><strong>ShareEx Serial:</strong></td>
                                <td>{{ $order->shipping_serial ?: 'Not assigned' }}</td>
                            </tr>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Customer Information -->
            <div class="col-md-6">
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Customer Information</h5>
                    </div>
                    <div class="card-body">
                        @php
                            $shippingAddress = $order->shipping_address;
                            $billingAddress = $order->billing_address;
                        @endphp
                        
                        <h6>Shipping Address</h6>
                        <p>
                            {{ $shippingAddress['first_name'] ?? '' }} {{ $shippingAddress['last_name'] ?? '' }}<br>
                            {{ $shippingAddress['address1'] ?? '' }}<br>
                            @if($shippingAddress['address2'] ?? false)
                                {{ $shippingAddress['address2'] }}<br>
                            @endif
                            {{ $shippingAddress['city'] ?? '' }}, {{ $shippingAddress['province'] ?? '' }} {{ $shippingAddress['zip'] ?? '' }}<br>
                            {{ $shippingAddress['country'] ?? '' }}<br>
                            Phone: {{ $shippingAddress['phone'] ?? 'Not provided' }}
                        </p>

                        @if($billingAddress && $billingAddress !== $shippingAddress)
                            <hr>
                            <h6>Billing Address</h6>
                            <p>
                                {{ $billingAddress['first_name'] ?? '' }} {{ $billingAddress['last_name'] ?? '' }}<br>
                                {{ $billingAddress['address1'] ?? '' }}<br>
                                @if($billingAddress['address2'] ?? false)
                                    {{ $billingAddress['address2'] }}<br>
                                @endif
                                {{ $billingAddress['city'] ?? '' }}, {{ $billingAddress['province'] ?? '' }} {{ $billingAddress['zip'] ?? '' }}<br>
                                {{ $billingAddress['country'] ?? '' }}
                            </p>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <!-- Shipping Management -->
        <div class="row">
            <div class="col-12">
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Shipping Management</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <h6>ShareEx City Assignment</h6>
                                @if(!$order->shareex_shipping_city)
                                    <form action="{{ route('admin.orders.update-city', $order->id) }}" method="POST" class="mb-3">
                                        @csrf
                                        <div class="input-group">
                                            <select name="shareex_shipping_city" class="form-select" required>
                                                <option value="">Select ShareEx city</option>
                                                @foreach(array_unique(config('shareex_areas')) as $city)
                                                    <option value="{{ $city }}">{{ $city }}</option>
                                                @endforeach
                                            </select>
                                            <button type="submit" class="btn btn-primary">Assign City</button>
                                        </div>
                                    </form>
                                @else
                                    <div class="d-flex align-items-center gap-3 mb-3">
                                        <span class="badge bg-light text-dark fs-6">{{ $order->shareex_shipping_city }}</span>
                                        <button class="btn btn-sm btn-outline-secondary" onclick="showCityChangeForm()">
                                            <i class="bi bi-pencil"></i> Change City
                                        </button>
                                    </div>
                                    
                                    <div id="cityChangeForm" style="display: none;" class="mb-3">
                                        <form action="{{ route('admin.orders.update-city', $order->id) }}" method="POST">
                                            @csrf
                                            <div class="input-group">
                                                <select name="shareex_shipping_city" class="form-select" required>
                                                    <option value="">Select new city</option>
                                                    @foreach(array_unique(config('shareex_areas')) as $city)
                                                        <option value="{{ $city }}" {{ $city === $order->shareex_shipping_city ? 'selected' : '' }}>{{ $city }}</option>
                                                    @endforeach
                                                </select>
                                                <button type="submit" class="btn btn-primary">Update City</button>
                                                <button type="button" class="btn btn-secondary" onclick="hideCityChangeForm()">Cancel</button>
                                            </div>
                                        </form>
                                    </div>
                                @endif
                            </div>
                            
                            <div class="col-md-6">
                                <h6>Shipping Status</h6>
                                @if($order->shipping_status === 'ready_to_ship' && $order->shareex_shipping_city)
                                    <form action="{{ route('admin.orders.update-status', $order->id) }}" method="POST">
                                        @csrf
                                        <input type="hidden" name="shipping_status" value="shipped">
                                        <button type="submit" class="btn btn-success">
                                            <i class="bi bi-truck"></i> Mark as Shipped
                                        </button>
                                    </form>
                                @elseif($order->shipping_status === 'awaiting_for_shipping_city')
                                    <p class="text-muted">Please assign a ShareEx city first</p>
                                @elseif($order->shipping_status === 'shipped')
                                    <span class="badge bg-success fs-6">Order has been shipped</span>
                                @else
                                    <p class="text-muted">Order is in {{ ucfirst(str_replace('_', ' ', $order->shipping_status)) }} status</p>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Order Items -->
        <div class="row">
            <div class="col-12">
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Order Items</h5>
                    </div>
                    <div class="card-body">
                        @if($order->line_items && count($order->line_items) > 0)
                            <div class="table-responsive">
                                <table class="table table-striped" id="orderItemsTable">
                                    <thead>
                                        <tr>
                                            <th>Product</th>
                                            <th>SKU</th>
                                            <th>Quantity</th>
                                            <th>Price</th>
                                            <th>Total</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($order->line_items as $item)
                                            <tr>
                                                <td>
                                                    <strong>{{ $item['name'] ?? 'Unknown Product' }}</strong>
                                                    @if($item['variant_title'] ?? false)
                                                        <br><small class="text-muted">{{ $item['variant_title'] }}</small>
                                                    @endif
                                                </td>
                                                <td>{{ $item['sku'] ?? 'No SKU' }}</td>
                                                <td>{{ $item['quantity'] ?? 1 }}</td>
                                                <td>{{ $item['price'] ?? '0.00' }}</td>
                                                <td>{{ ($item['price'] ?? 0) * ($item['quantity'] ?? 1) }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @else
                            <p class="text-muted">No line items available</p>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <!-- Shipping Logs -->
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Shipping Logs</h5>
                    </div>
                    <div class="card-body">
                        @if($order->logs && count($order->logs) > 0)
                            <div class="table-responsive">
                                <table class="table table-sm" id="shippingLogsTable">
                                    <thead>
                                        <tr>
                                            <th>Date</th>
                                            <th>Action</th>
                                            <th>Status</th>
                                            <th>Message</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($order->logs->sortByDesc('created_at') as $log)
                                            <tr>
                                                <td>{{ $log->created_at->format('Y-m-d H:i:s') }}</td>
                                                <td>{{ $log->action }}</td>
                                                <td>
                                                    <span class="badge bg-{{ $log->status === 'success' ? 'success' : ($log->status === 'error' ? 'danger' : 'warning') }}">
                                                        {{ ucfirst($log->status) }}
                                                    </span>
                                                </td>
                                                <td>{{ $log->message }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @else
                            <p class="text-muted">No shipping logs available</p>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        $(document).ready(function() {
            // Initialize Order Items DataTable
            if ($('#orderItemsTable').length) {
                $('#orderItemsTable').DataTable({
                    responsive: true,
                    pageLength: 10,
                    lengthMenu: [[5, 10, 25, -1], [5, 10, 25, "All"]],
                    order: [[0, 'asc']], // Sort by Product name
                    columnDefs: [
                        {
                            targets: [1, 2, 3, 4], // SKU, Quantity, Price, Total columns
                            orderable: false
                        }
                    ],
                    language: {
                        search: "Search items:",
                        lengthMenu: "Show _MENU_ items per page",
                        info: "Showing _START_ to _END_ of _TOTAL_ items",
                        infoEmpty: "Showing 0 to 0 of 0 items",
                        infoFiltered: "(filtered from _MAX_ total items)",
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
            }

            // Initialize Shipping Logs DataTable
            if ($('#shippingLogsTable').length) {
                $('#shippingLogsTable').DataTable({
                    responsive: true,
                    pageLength: 10,
                    lengthMenu: [[5, 10, 25, -1], [5, 10, 25, "All"]],
                    order: [[0, 'desc']], // Sort by Date descending (newest first)
                    columnDefs: [
                        {
                            targets: [1, 2, 3], // Action, Status, Message columns
                            orderable: false
                        }
                    ],
                    language: {
                        search: "Search logs:",
                        lengthMenu: "Show _MENU_ logs per page",
                        info: "Showing _START_ to _END_ of _TOTAL_ logs",
                        infoEmpty: "Showing 0 to 0 of 0 logs",
                        infoFiltered: "(filtered from _MAX_ total logs)",
                        paginate: {
                            first: "First",
                            last: "Last",
                            next: "Next",
                            previous: "Previous"
                        }
                    },
                    dom: '<"row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6"f>>' +
                         '<"row"<"col-sm-12"tr>>' +
                         '<"row"<"col-sm-12 col-md-5"i><"col-sm-12 col-md-7"p>>',
                    initComplete: function() {
                        // Add custom filter for status
                        this.api().columns().every(function() {
                            var column = this;
                            var header = $(column.header());
                            
                            // Add filter for status column
                            if (header.text().includes('Status')) {
                                var select = $('<select class="form-select form-select-sm ms-2"><option value="">All Statuses</option></select>')
                                    .appendTo(header)
                                    .on('change', function() {
                                        var val = $.fn.dataTable.util.escapeRegex($(this).val());
                                        column.search(val ? '^' + val + '$' : '', true, false).draw();
                                    });

                                column.data().unique().sort().each(function(d, j) {
                                    select.append('<option value="' + d + '">' + d + '</option>');
                                });
                            }
                        });
                    }
                });
            }
        });

        function showCityChangeForm() {
            document.getElementById('cityChangeForm').style.display = 'block';
        }
        
        function hideCityChangeForm() {
            document.getElementById('cityChangeForm').style.display = 'none';
        }
    </script>
@endsection 
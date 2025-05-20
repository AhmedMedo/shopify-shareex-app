@extends('admin.layouts.app')

@section('content')
    <div class="container-fluid">
        <h1 class="mb-4">Shopify Orders</h1>

        <div class="card">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead>
                        <tr>
                            <th>Order ID</th>
                            <th>Customer</th>
                            <th>Phone</th>
                            <th>Email</th>
                            <th>Shipping City</th>
                            <th>ShareEx City</th>
                            <th>ShareEx Serial</th>
                            <th>Last Shipment Action</th>
                            <th>Shipping Status</th>
                            <th>Actions</th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach($orders as $order)
                            @php
                                $shippingAddress = $order->shipping_address;
                                $latestLog = $order->logs->first();
                            @endphp
                            <tr>
                                <td>{{ $order->order_number }}</td>
                                <td>{{ $shippingAddress['first_name'] ?? '' }} {{ $shippingAddress['last_name'] ?? '' }}</td>
                                <td>{{ $shippingAddress['phone'] ?? '' }}</td>
                                <td>{{ $order->email }}</td>
                                <td>{{ $shippingAddress['city'] ?? '' }}</td>
                                <td>
                                <td>
                                    @if(!$order->shareex_shipping_city)
                                        <div class="d-flex align-items-center gap-2">
                                            <select class="form-select form-select-sm city-select"
                                                    data-order-id="{{ $order->id }}"
                                                    style="width: 120px;">
                                                <option value="">Select city</option>
                                                @foreach(config('shareex_areas') as $city)
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
                                </td>
                                <td>
                                    @if($latestLog && $latestLog->shareex_serial_number)
                                        {{ $latestLog->shareex_serial_number }}
                                    @else
                                        -
                                    @endif
                                </td>
                                <td>
                                    @if($latestLog)
                                        <span class="badge bg-{{ $latestLog->status === 'success' ? 'success' : 'danger' }}">
                                            {{ ucfirst($latestLog->status) }}
                                        </span>
                                        <small class="text-muted d-block">
                                            {{ $latestLog->created_at->diffForHumans() }}
                                        </small>
                                    @else
                                        No shipment action
                                    @endif
                                </td>
                                <td>
                                    <span class="badge bg-{{ $order->shipping_status === 'ready_to_ship' ? 'success' : ($order->shipping_status === 'shipped' ? 'info' : 'warning') }}">
                                        {{ ucfirst(str_replace('_', ' ', $order->shipping_status)) }}
                                    </span>
                                </td>
                                <td>
                                    @if(!$order->shareex_shipping_city)
                                        <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#cityModal-{{ $order->id }}">
                                            Set City
                                        </button>

                                        <!-- City Selection Modal -->
                                    @endif

                                    @if($order->shipping_status === 'ready_to_ship')
                                        <form action="{{ route('admin.orders.update-status', $order->id) }}" method="POST" class="d-inline">
                                            @csrf
                                            <input type="hidden" name="shipping_status" value="shipped">
                                            <button type="submit" class="btn btn-sm btn-success">Mark as Shipped</button>
                                        </form>
                                    @endif
                                </td>
                            </tr>
                            <div class="modal fade" id="cityModal-{{ $order->id }}" tabindex="-1" aria-hidden="true">
                                <div class="modal-dialog">
                                    <div class="modal-content">
                                        <form action="{{ route('admin.orders.update-city', $order->id) }}" method="POST">
                                            @csrf
                                            <div class="modal-header">
                                                <h5 class="modal-title">Set ShareEx City for Order #{{ $order->order_number }}</h5>
                                                <button type="button" class="btn-close" data-bs-close="modal" aria-label="Close"></button>
                                            </div>
                                            <div class="modal-body">
                                                <div class="mb-3">
                                                    <label class="form-label">Select City</label>
                                                    <select name="shareex_shipping_city" class="form-select" required>
                                                        <option value="">Select a city</option>
                                                        @foreach(config('shareex_areas') as $city)
                                                            <option value="{{ $city }}">{{ $city }}</option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                                <button type="submit" class="btn btn-primary">Save changes</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>

                        @endforeach
                        </tbody>
                    </table>

                </div>

                <div class="mt-3">
                    {{ $orders->links() }}
                </div>
            </div>
        </div>
    </div>
@endsection
@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Enable save button when city is selected
            document.addEventListener('change', function(e) {
                if (e.target.classList.contains('city-select')) {
                    const orderId = e.target.dataset.orderId;
                    const saveBtn = document.querySelector(`.save-city-btn[data-order-id="${orderId}"]`);
                    saveBtn.disabled = e.target.value === '';
                }
            });

            // Save city selection
            document.addEventListener('click', async function(e) {
                if (e.target.closest('.save-city-btn')) {
                    const btn = e.target.closest('.save-city-btn');
                    const orderId = btn.dataset.orderId;
                    const select = document.querySelector(`.city-select[data-order-id="${orderId}"]`);
                    const city = select.value;

                    if (!city) return;

                    btn.disabled = true;
                    btn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status"></span>';

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
                            btn.innerHTML = '<i class="bi bi-check-lg"></i>';
                            btn.disabled = false;
                        }
                    } catch (error) {
                        console.error('Error:', error);
                        alert('An error occurred');
                        btn.innerHTML = '<i class="bi bi-check-lg"></i>';
                        btn.disabled = false;
                    }
                }
            });

            // Change city button
            document.addEventListener('click', function(e) {
                if (e.target.closest('.change-city-btn')) {
                    const btn = e.target.closest('.change-city-btn');
                    const orderId = btn.dataset.orderId;
                    const currentCity = btn.dataset.currentCity;
                    const td = btn.closest('td');

                    td.innerHTML = `
                <div class="d-flex align-items-center gap-2">
                    <select class="form-select form-select-sm city-select"
                            data-order-id="${orderId}"
                            style="width: 120px;">
                        <option value="">Select city</option>
                        @foreach(config('shareex_areas') as $city)
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
            `;
                }
            });

            // Cancel change
            document.addEventListener('click', function(e) {
                if (e.target.closest('.cancel-change-btn')) {
                    const btn = e.target.closest('.cancel-change-btn');
                    const orderId = btn.dataset.orderId;
                    const currentCity = btn.dataset.currentCity;
                    const td = btn.closest('td');

                    td.innerHTML = `
                <div class="d-flex align-items-center gap-2">
                    <span class="badge bg-light text-dark">${currentCity}</span>
                    <button class="btn btn-sm btn-outline-secondary change-city-btn py-1 px-2"
                            data-order-id="${orderId}"
                            data-current-city="${currentCity}">
                        <i class="bi bi-pencil"></i>
                    </button>
                </div>
            `;
                }
            });
        });
    </script>
@endpush

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
                            <th>Shipping Address</th>
                            <th>Shipping City</th>
                            <th>ShareEx City</th>
                            <th>Shipping Status</th>
                            <th>Actions</th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach($orders as $order)
                            @php
                                $shippingAddress = $order->shipping_address;
                                $customer = $order->customer;
                            @endphp
                            <tr>
                                <td>{{ $order->order_number }}</td>
                                <td>{{ $shippingAddress['first_name'] ?? '' }} {{ $shippingAddress['last_name'] ?? '' }}</td>
                                <td>{{ $shippingAddress['phone'] ?? '' }}</td>
                                <td>{{ $order->email }}</td>
                                <td>
                                    {{ $shippingAddress['address1'] ?? '' }}<br>
                                    {{ $shippingAddress['address2'] ?? '' }}
                                </td>
                                <td>{{ $shippingAddress['city'] ?? '' }}</td>
                                <td>{{ $order->shareex_shipping_city ?? 'Not set' }}</td>
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
                                    @endif

                                    @if($order->shipping_status === 'ready_to_ship')
                                        <form action="{{ route('admin.orders.update-status', $order->id) }}" method="POST" class="d-inline">
                                            @csrf
                                            <input type="hidden" name="shipping_status" value="shipped">
                                            <button type="submit" class="btn btn-sm btn-success">Send to Shareex</button>
                                        </form>
                                    @endif
                                </td>
                            </tr>
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

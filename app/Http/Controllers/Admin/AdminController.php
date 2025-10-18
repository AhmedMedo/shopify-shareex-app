<?php

namespace App\Http\Controllers\Admin;

use App\Enum\ShippingStatusEnum;
use App\Http\Controllers\Controller;
use App\Models\ShopifyOrder;
use App\Services\Shareex\ShippingService;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class AdminController extends Controller
{

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'api_username' => 'required',
            'password' => 'required'
        ]);

        if (Auth::guard('admin')->attempt($credentials)) {
            $request->session()->regenerate();
            return redirect()->intended('admin/');
        }

        return back()->withErrors([
            'api_username' => 'The provided credentials do not match our records.',
        ]);
    }

    public function index(Request $request)
    {
        $activeTab = $request->get('tab', 'pending');
        $shopId = Auth::guard('admin')->user()->shop_id;

        $query = ShopifyOrder::with([
            'shop',
            'logs' => function($query) {
                $query->where('action', 'SendShipment')
                    ->latest()
                    ->limit(1);
            }
        ])->where('shop_id', $shopId);

        // Filter orders based on active tab
        switch ($activeTab) {
            case 'pending':
                $query->where('shipping_status', ShippingStatusEnum::PENDING->value);
                break;
            case 'not_shipped':
                $query->whereIn('shipping_status', [
                    ShippingStatusEnum::READY_TO_SHIP->value,
                    ShippingStatusEnum::AWAINTING_FOR_SHIPPING_CITY->value
                ]);
                break;
            case 'shipped':
                $query->where('shipping_status', ShippingStatusEnum::SHIPPED->value);
                break;
            default:
                $query->where('shipping_status', ShippingStatusEnum::PENDING->value);
        }

        $orders = $query->orderBy('created_at', 'desc')->get();

        return view('admin.index', compact('orders', 'activeTab'));
    }

    public function showOrder($orderId)
    {
        $order = ShopifyOrder::with(['shop', 'logs'])->findOrFail($orderId);

        // Check if user has access to this order
        if ($order->shop_id !== Auth::guard('admin')->user()->shop_id) {
            abort(403, 'Unauthorized access to this order.');
        }

        return view('admin.order-details', compact('order'));
    }

    public function updateShippingCity(Request $request, $orderId)
    {
        $request->validate([
            'shareex_shipping_city' => 'required|string',
        ]);

        $order = ShopifyOrder::findOrFail($orderId);

        // Check if user has access to this order
        if ($order->shop_id !== Auth::guard('admin')->user()->shop_id) {
            abort(403, 'Unauthorized access to this order.');
        }

        $toUpdate['shareex_shipping_city'] = $request->shareex_shipping_city;
        if ($order->shipping_status == ShippingStatusEnum::AWAINTING_FOR_SHIPPING_CITY->value)
        {
            $toUpdate['shipping_status'] = ShippingStatusEnum::READY_TO_SHIP->value;
        }
        $order->update($toUpdate);

        return back()->with('success', 'Shipping city updated successfully');
    }

    public function updateShippingStatus(Request $request, $orderId)
    {
        $request->validate([
            'shipping_status' => 'required|string'
        ]);

        $order = ShopifyOrder::findOrFail($orderId);

        // Check if user has access to this order
        if ($order->shop_id !== Auth::guard('admin')->user()->shop_id) {
            abort(403, 'Unauthorized access to this order.');
        }

        $order->update([
            'shipping_status' => $request->shipping_status
        ]);

        try {
            $service = new ShippingService($order);
            $service->sendToShareex();

        }catch (Exception $e) {
            Log::error('Error updating shipping status: ' . $e->getMessage());
        }

        return back()->with('success', 'Shipping status updated successfully');
    }

    public function logout(Request $request)
    {
        Auth::guard('admin')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect('/admin/login');
    }
}

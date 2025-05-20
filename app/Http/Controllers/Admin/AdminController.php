<?php

namespace App\Http\Controllers\Admin;

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

    public function index()
    {
        $orders = ShopifyOrder::with([
            'shop',
            'logs' => function($query) {
                $query->where('action', 'SendShipment')
                    ->latest()
                    ->limit(1);
            }
        ])
            ->where('shop_id', Auth::guard('admin')->user()->shop_id)
            ->orderBy('processed_at', 'desc')
            ->paginate(20);

        return view('admin.index', compact('orders'));

    }

    public function updateShippingCity(Request $request, $orderId)
    {
        $request->validate([
            'shareex_shipping_city' => 'required|string'
        ]);

        $order = ShopifyOrder::findOrFail($orderId);
        $order->update([
            'shareex_shipping_city' => $request->shareex_shipping_city
        ]);

        return back()->with('success', 'Shipping city updated successfully');
    }

    public function updateShippingStatus(Request $request, $orderId)
    {
        $request->validate([
            'shipping_status' => 'required|string'
        ]);

        $order = ShopifyOrder::findOrFail($orderId);
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

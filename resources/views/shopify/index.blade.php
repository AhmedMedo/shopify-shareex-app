@extends('shopify-app::layouts.default')

@section('content')
    {{-- The main content of your application after authentication --}}
    {{-- This will load the Shareex Credentials settings page by default --}}
    @livewire('shopify.settings.shareex-credentials')

    {{-- You can add navigation here to other Livewire components/pages if needed --}}
    {{-- For example:
    <nav class="Polaris-Navigation">
        <ul>
            <li class="Polaris-Navigation__ListItem">
                <a href="{{ route('shopify.area-mappings') }}" class="Polaris-Navigation__Item">
                    <span class="Polaris-Navigation__Text">Area Mappings</span>
                </a>
            </li>
            <li class="Polaris-Navigation__ListItem">
                <a href="{{ route('shopify.shipping-rules') }}" class="Polaris-Navigation__Item">
                    <span class="Polaris-Navigation__Text">Shipping Rate Rules</span>
                </a>
            </li>
            <li class="Polaris-Navigation__ListItem">
                <a href="{{ route('shopify.shipment-logs') }}" class="Polaris-Navigation__Item">
                    <span class="Polaris-Navigation__Text">Shipment Logs</span>
                </a>
            </li>
        </ul>
    </nav>
    --}}
@endsection


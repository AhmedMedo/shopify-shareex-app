# WordPress ShareeX Shipping Plugin Implementation Plan

## Overview

Create a WordPress/WooCommerce plugin to integrate with the ShareeX shipping API for Egypt-based e-commerce stores. This plugin will enable store administrators to:
- Configure ShareeX API credentials
- Auto-map WooCommerce cities to ShareeX Arabic cities
- Send shipments to ShareeX from WooCommerce orders
- Track shipment status and history
- Manage all shipments from a central admin dashboard

## Background Context

Based on analysis of the existing Shopify ShareeX integration:
- **API Base URL**: Configurable (e.g., `https://api.shareex.eg/`)
- **Endpoints**:
  - `POST /api/shipments.asmx/SendShipment` - Create shipment
  - `GET /api/shipments.asmx/GetShipmentLastStatus` - Get current status
  - `GET /api/shipments.asmx/GetShipmentHistory` - Get full history
- **Authentication**: Username (`uname`) and Password (`upass`) passed with each request
- **City Mapping**: 300+ Arabic city names with English key mappings

---

## User Review Required

> [!IMPORTANT]
> **Decision Points:**
> 1. Should we require WooCommerce as a dependency, or make it optional for other e-commerce plugins?
> 2. Do you want automatic shipment creation on order status change (e.g., "Processing"), or manual-only via admin interface?
> 3. Should the city mapping be editable via admin UI, or config file only?
> 4. Do you need webhook support for real-time status updates from ShareeX?

> [!CAUTION]
> **Breaking Change Consideration:** If you plan to use this with your existing database from the Shopify app, we'll need migration scripts. If this is a standalone WordPress plugin, no migration is needed.

---

## Proposed Architecture

### Plugin Structure

```
shareex-shipping/
├── shareex-shipping.php              # Main plugin file
├── readme.txt                        # WordPress readme
├── composer.json                     # Dependencies
│
├── assets/
│   ├── css/
│   │   └── admin.css                 # Admin styles
│   └── js/
│       └── admin.js                  # Admin scripts
│
├── includes/
│   ├── class-shareex-plugin.php      # Plugin bootstrap
│   ├── class-shareex-loader.php      # Hook loader
│   └── class-shareex-i18n.php        # Internationalization
│
├── admin/
│   ├── class-shareex-admin.php       # Admin functionality
│   ├── class-shareex-settings.php    # Settings page
│   ├── class-shareex-orders-page.php # Orders management page
│   └── partials/
│       ├── settings-page.php         # Settings template
│       ├── orders-page.php           # Orders list template
│       └── order-metabox.php         # Order metabox template
│
├── includes/
│   ├── api/
│   │   ├── class-shareex-api.php           # API client
│   │   └── class-shareex-api-response.php  # Response handler
│   │
│   ├── models/
│   │   ├── class-shareex-shipment.php      # Shipment model
│   │   └── class-shareex-shipment-log.php  # Log model
│   │
│   ├── services/
│   │   ├── class-city-mapper.php           # City mapping service
│   │   ├── class-shipment-service.php      # Shipment business logic
│   │   └── class-logger-service.php        # Enhanced logging
│   │
│   └── integrations/
│       └── class-woocommerce-integration.php # WooCommerce hooks
│
├── config/
│   ├── shareex-areas.php              # Arabic city list
│   └── shareex-areas-mapped.php       # City mappings
│
└── logs/
    └── (auto-generated log files)
```

---

## Proposed Changes

### Core Plugin Files

#### [NEW] [shareex-shipping.php](file:///path/to/plugin/shareex-shipping.php)
Main plugin bootstrap file with WordPress plugin headers and initialization.

```php
/**
 * Plugin Name: ShareeX Shipping
 * Description: WooCommerce integration for ShareeX shipping in Egypt
 * Version: 1.0.0
 * Requires at least: 5.0
 * Requires PHP: 7.4
 * WC requires at least: 5.0
 */
```

---

### API Layer

#### [NEW] [class-shareex-api.php](file:///path/to/plugin/includes/api/class-shareex-api.php)

ShareeX API client with methods:
- `send_shipment(array $data): ?array` - Create new shipment
- `get_shipment_status(string $serial): ?array` - Get last status
- `get_shipment_history(string $serial): ?array` - Get full history
- `test_connection(): bool` - Validate credentials

Key features:
- Credential encryption using WordPress options
- Request/response logging
- Error handling with retry logic

---

### Admin Interface

#### [NEW] [class-shareex-settings.php](file:///path/to/plugin/admin/class-shareex-settings.php)

Settings page with tabs:

| Tab | Fields |
|-----|--------|
| **General** | API Base URL, Username, Password, Default Area |
| **City Mapping** | View/edit city mappings (searchable table) |
| **Logs** | View recent API logs with filtering |
| **Advanced** | Debug mode, Auto-sync settings |

#### [NEW] [class-shareex-orders-page.php](file:///path/to/plugin/admin/class-shareex-orders-page.php)

Custom admin page showing all ShareeX orders:

| Column | Description |
|--------|-------------|
| Order # | WooCommerce order number (linked) |
| Customer | Customer name |
| City | Mapped ShareeX city |
| Serial | ShareeX tracking number |
| Status | Current shipment status |
| Actions | View, Refresh Status, Print Label |

Features:
- Bulk actions (send multiple orders)
- Status filter dropdown
- Date range filter
- Search by customer/serial
- Export to CSV

---

### WooCommerce Integration

#### [NEW] [class-woocommerce-integration.php](file:///path/to/plugin/includes/integrations/class-woocommerce-integration.php)

WooCommerce hooks:

```php
// Order metabox in edit order page
add_action('add_meta_boxes', [$this, 'add_order_metabox']);

// Status column in orders list
add_filter('manage_edit-shop_order_columns', [$this, 'add_column']);
add_action('manage_shop_order_posts_custom_column', [$this, 'render_column']);

// Bulk action to send to ShareeX
add_filter('bulk_actions-edit-shop_order', [$this, 'add_bulk_action']);

// AJAX handlers
add_action('wp_ajax_shareex_send_order', [$this, 'ajax_send_order']);
add_action('wp_ajax_shareex_get_status', [$this, 'ajax_get_status']);
```

#### Order Metabox Display

The metabox on individual order pages will show:
- **ShareeX Serial**: The tracking number (if sent)
- **Current Status**: With status badge
- **Status History**: Collapsible accordion with timestamps
- **City Mapping**: Show original city → mapped Arabic city
- **Send Button**: To send/resend to ShareeX
- **Refresh Button**: To update status

---

### City Mapping Service

#### [NEW] [class-city-mapper.php](file:///path/to/plugin/includes/services/class-city-mapper.php)

Port from Shopify implementation with enhancements:
- Load mappings from config files
- Manual mapping (fuzzy match with 80%+ similarity)
- Optional AI mapping via OpenAI (configurable)
- Admin UI for viewing/editing mappings
- Logging of unmapped cities

---

### Database Schema

#### Custom Tables

```sql
-- Shipment logs table
CREATE TABLE {prefix}shareex_logs (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    order_id BIGINT UNSIGNED NOT NULL,
    action VARCHAR(50) NOT NULL,           -- 'send', 'status_check', 'history'
    request_payload LONGTEXT,
    response_payload LONGTEXT,
    status VARCHAR(20) NOT NULL,           -- 'success', 'failed'
    error_message TEXT,
    shareex_serial VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    INDEX idx_order_id (order_id),
    INDEX idx_serial (shareex_serial),
    INDEX idx_created_at (created_at)
);

-- City mapping overrides (admin-defined)
CREATE TABLE {prefix}shareex_city_mappings (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    source_city VARCHAR(255) NOT NULL,     -- Original customer input
    shareex_city VARCHAR(255) NOT NULL,    -- Arabic city name
    mapping_type VARCHAR(20) DEFAULT 'manual', -- 'manual', 'auto', 'ai'
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    UNIQUE KEY uk_source (source_city)
);
```

#### WooCommerce Order Meta

```php
// Stored in order meta
'_shareex_serial'         => 'ShareeX tracking number'
'_shareex_city'           => 'Mapped Arabic city name'
'_shareex_status'         => 'Current status'
'_shareex_last_updated'   => 'Timestamp of last status update'
```

---

### Logging System

#### [NEW] [class-logger-service.php](file:///path/to/plugin/includes/services/class-logger-service.php)

Enhanced logging features:
- Log levels: DEBUG, INFO, WARNING, ERROR
- Log rotation (daily files, keep 30 days)
- Admin UI to view/search logs
- Export logs to file
- Optional WooCommerce logger integration

```php
class Logger_Service {
    public function debug($message, $context = []);
    public function info($message, $context = []);
    public function warning($message, $context = []);
    public function error($message, $context = []);
    public function api_log($order_id, $action, $request, $response, $status);
}
```

Log entry format:
```
[2024-01-01 12:00:00] INFO: Shipment sent | Order: #1234 | Serial: ABC123 | City: المعادى
[2024-01-01 12:00:01] ERROR: API failed | Order: #1235 | Error: Invalid credentials
```

---

### Recommendations

> [!TIP]
> **Best Practices to Implement:**

1. **Webhook Support**: If ShareeX provides webhooks for status updates, implement an endpoint to receive them automatically instead of polling.

2. **Caching**: Cache ShareeX areas list and city mappings using WordPress transients (refresh daily).

3. **Background Processing**: Use WP Background Processing library for bulk operations to avoid timeouts.

4. **Internationalization**: Prepare all strings for translation (Arabic RTL support).

5. **REST API**: Expose endpoints for potential mobile app integration:
   - `GET /wp-json/shareex/v1/orders`
   - `POST /wp-json/shareex/v1/orders/{id}/send`
   - `GET /wp-json/shareex/v1/orders/{id}/status`

6. **Status Sync Cron**: Add a WP cron job to periodically update shipment statuses.

7. **Email Notifications**: Send customer emails with tracking info when shipment is created.

---

## Verification Plan

### Automated Tests

```bash
# Unit tests for API client
./vendor/bin/phpunit tests/unit/ApiTest.php

# Integration tests (requires test credentials)
./vendor/bin/phpunit tests/integration/ShipmentTest.php

# City mapping tests
./vendor/bin/phpunit tests/unit/CityMapperTest.php
```

### Manual Verification

1. **Settings Flow**:
   - Install plugin → Settings page loads correctly
   - Enter credentials → Test connection works
   - Save settings → Values persist after refresh

2. **Order Flow**:
   - Create test order → Metabox shows "Not sent"
   - Click Send → Serial number appears
   - Click Refresh → Status updates
   - View History → Shows all status changes

3. **Bulk Operations**:
   - Select 5 orders → Bulk send works
   - Progress indicator shows
   - All serials updated

4. **Logging**:
   - Perform API actions → Logs appear in admin
   - Filter by date/status works
   - Export downloads file

---

## Implementation Timeline

| Phase | Tasks | Estimated |
|-------|-------|-----------|
| **Phase 1** | Plugin structure, Settings page, API client | 2-3 days |
| **Phase 2** | WooCommerce integration, Order metabox | 2 days |
| **Phase 3** | City mapping service, Admin UI | 1-2 days |
| **Phase 4** | Orders management page, Bulk actions | 1-2 days |
| **Phase 5** | Logging system, Polish, Testing | 1-2 days |

**Total Estimate: 7-11 days**

---

## Next Steps

1. Confirm the design decisions in the "User Review Required" section
2. Set up the WordPress development environment
3. Begin Phase 1 implementation

Would you like me to proceed with implementation after you confirm the design decisions?

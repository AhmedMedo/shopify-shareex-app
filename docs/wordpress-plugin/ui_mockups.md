# ShareeX WordPress Plugin - UI Demo Mockups

This document showcases the proposed user interface for the ShareeX Shipping WordPress plugin.

---

## 1. Settings Page - General Tab

The main settings page where administrators configure ShareeX API credentials and default settings.

**Features:**
- API Base URL, Username, and Password fields
- Test Connection button to validate credentials
- Default Area dropdown for fallback city
- Success/error notifications

![Settings Page - General Tab](./settings_page_1766928706616.png)

---

## 2. City Mapping Page

The City Mapping tab allows you to view, search, and manage city mappings between English input and ShareeX Arabic city names.

**Features:**
- Searchable table of 328+ city mappings
- Add custom mappings for unmapped cities
- Edit/delete existing mappings
- "Unmapped Cities" alert for admin review

![City Mapping Page](./city_mapping_page_1766928781875.png)

---

## 3. Logs Page

The Logs tab provides a detailed view of all API interactions and plugin activities.

**Features:**
- Color-coded log levels (Info, Warning, Error)
- Filter by level and date range
- Export logs to file
- Clear old logs option

![Logs Page](./logs_page_1766928800645.png)

---

## 4. Order Metabox

This metabox appears on individual WooCommerce order edit pages, showing ShareeX shipping details.

**Features:**
- ShareeX serial number with copy button
- Current status with color badge
- Mapped city (Arabic) with original city shown
- View History accordion with timeline
- Refresh Status and View History buttons

![Order Metabox](./order_metabox_1766928725557.png)

---

## 5. ShareeX Orders Management Page

A dedicated admin page to manage all ShareeX shipments in one place.

**Features:**
- Filterable table by status, date, and search
- Bulk actions (send multiple orders)
- Export to CSV
- Quick actions (View, Refresh) per order
- Status badges (Delivered, In Transit, Pending, Failed)

![Orders Management Page](./orders_page_1766928748110.png)

---

## UI Summary Table

| Screen | Purpose | Key Actions |
|--------|---------|-------------|
| **Settings - General** | Configure API credentials | Test Connection, Save |
| **Settings - City Mapping** | Manage city mappings | Search, Add, Edit, Delete |
| **Settings - Logs** | View activity logs | Filter, Export, Clear |
| **Order Metabox** | Per-order shipping details | Send, Refresh, View History |
| **Orders Page** | Manage all shipments | Bulk Send, Filter, Export |

---

## Navigation Flow

```mermaid
flowchart LR
    A[WordPress Admin] --> B[ShareeX Menu]
    B --> C[Settings]
    B --> D[Orders]
    C --> C1[General]
    C --> C2[City Mapping]
    C --> C3[Logs]
    D --> D1[All Orders Table]
    D1 --> D2[Single Order View]
```

---

## Color Scheme

| Element | Color | Usage |
|---------|-------|-------|
| Primary Button | `#0073aa` | Save, Send, Primary Actions |
| Secondary Button | `#f7f7f7` | Cancel, Secondary Actions |
| Success Badge | `#46b450` | Delivered status |
| Warning Badge | `#ffb900` | Pending status |
| Info Badge | `#00a0d2` | In Transit status |
| Error Badge | `#dc3232` | Failed status |

These colors follow WordPress admin UI guidelines for consistency.

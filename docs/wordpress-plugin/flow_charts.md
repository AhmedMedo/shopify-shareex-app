# ShareeX WordPress Plugin - Flow Charts

## 1. System Architecture Overview

```mermaid
flowchart TB
    subgraph WordPress["WordPress / WooCommerce"]
        WC[WooCommerce Orders]
        Admin[Admin Dashboard]
        Settings[Plugin Settings]
        Logs[Logs Viewer]
    end
    
    subgraph Plugin["ShareeX Plugin"]
        API[API Client]
        Mapper[City Mapper]
        Service[Shipment Service]
        Logger[Logger Service]
    end
    
    subgraph External["External Services"]
        ShareeX[ShareeX API]
        DB[(Database)]
    end
    
    WC --> Service
    Admin --> Service
    Settings --> API
    Service --> API
    Service --> Mapper
    Service --> Logger
    API --> ShareeX
    Logger --> DB
    Mapper --> DB
```

---

## 2. Order Shipment Flow

```mermaid
flowchart TD
    A[Customer Places Order] --> B{Order Status}
    B -->|Processing| C[Admin Views Order]
    C --> D[Click Send to ShareeX]
    D --> E[City Mapper Service]
    E --> F{City Found?}
    F -->|Yes| G[Prepare Shipment Data]
    F -->|No| H[Show Unmapped City Warning]
    H --> I[Admin Selects City Manually]
    I --> G
    G --> J[Call ShareeX API]
    J --> K{API Response}
    K -->|Success| L[Save Serial to Order Meta]
    L --> M[Log Success]
    M --> N[Update Order Status Display]
    K -->|Error| O[Log Error]
    O --> P[Show Error Message]
    P --> Q[Admin Can Retry]
    Q --> D
```

---

## 3. Status Tracking Flow

```mermaid
flowchart LR
    subgraph Manual["Manual Refresh"]
        A1[Admin Clicks Refresh] --> B1[Get Serial from Meta]
        B1 --> C1[Call GetShipmentLastStatus]
        C1 --> D1[Update Order Meta]
        D1 --> E1[Display New Status]
    end
    
    subgraph Auto["Auto Sync - Cron"]
        A2[WP Cron Triggers] --> B2[Get Pending Orders]
        B2 --> C2[Loop Each Order]
        C2 --> D2[Call GetShipmentLastStatus]
        D2 --> E2[Update Order Meta]
        E2 --> F2{More Orders?}
        F2 -->|Yes| C2
        F2 -->|No| G2[Log Sync Complete]
    end
    
    subgraph History["View History"]
        A3[Admin Clicks History] --> B3[Call GetShipmentHistory]
        B3 --> C3[Parse Response]
        C3 --> D3[Display Timeline]
    end
```

---

## 4. City Mapping Process

```mermaid
flowchart TD
    A[Customer City Input] --> B[Normalize String]
    B --> C{Check Manual Mapping}
    C -->|Found| D[Return Arabic City]
    C -->|Not Found| E{Check Config Mapping}
    E -->|Found| D
    E -->|Not Found| F{Fuzzy Match > 80%?}
    F -->|Yes| D
    F -->|No| G{AI Mapping Enabled?}
    G -->|Yes| H[Call OpenAI API]
    H --> I{Valid City Returned?}
    I -->|Yes| J[Cache Result]
    J --> D
    I -->|No| K[Return Null]
    G -->|No| K
    K --> L[Log Unmapped City]
    L --> M[Admin Reviews Later]
```

---

## 5. Admin Workflow

```mermaid
flowchart TB
    subgraph Settings["Settings Page Flow"]
        S1[Open Settings] --> S2[General Tab]
        S2 --> S3[Enter Credentials]
        S3 --> S4[Test Connection]
        S4 --> S5{Success?}
        S5 -->|Yes| S6[Save Settings]
        S5 -->|No| S7[Show Error]
    end
    
    subgraph Orders["Orders Management"]
        O1[Open ShareeX Orders] --> O2[View All Orders]
        O2 --> O3{Filter Options}
        O3 --> O4[By Status]
        O3 --> O5[By Date]
        O3 --> O6[Search]
        O4 --> O7[Display Filtered]
        O5 --> O7
        O6 --> O7
        O7 --> O8{Action}
        O8 --> O9[Send Single]
        O8 --> O10[Bulk Send]
        O8 --> O11[Export CSV]
    end
    
    subgraph Logs["Logs Management"]
        L1[Open Logs Tab] --> L2[View Recent Logs]
        L2 --> L3{Filter}
        L3 --> L4[By Level]
        L3 --> L5[By Date]
        L4 --> L6[Display Logs]
        L5 --> L6
        L6 --> L7[Export Logs]
    end
```

---

## 6. Data Flow Diagram

```mermaid
flowchart LR
    subgraph Input["Input Sources"]
        WC[WooCommerce Order]
        Admin[Admin Action]
        Cron[Scheduled Task]
    end
    
    subgraph Processing["Processing Layer"]
        Validate[Validate Data]
        Map[Map City]
        Prepare[Prepare Payload]
    end
    
    subgraph API["API Layer"]
        Send[Send Request]
        Parse[Parse Response]
        Handle[Handle Errors]
    end
    
    subgraph Storage["Data Storage"]
        Meta[Order Meta]
        Logs[Logs Table]
        Cache[Transient Cache]
    end
    
    WC --> Validate
    Admin --> Validate
    Cron --> Validate
    Validate --> Map
    Map --> Prepare
    Prepare --> Send
    Send --> Parse
    Parse --> Handle
    Handle --> Meta
    Handle --> Logs
    Map --> Cache
```

---

## 7. Status State Machine

```mermaid
stateDiagram-v2
    [*] --> NotSent: Order Created
    NotSent --> Pending: Send to ShareeX
    Pending --> InTransit: Picked Up
    InTransit --> OutForDelivery: Near Destination
    OutForDelivery --> Delivered: Successful
    OutForDelivery --> Failed: Delivery Failed
    Failed --> InTransit: Retry Delivery
    Delivered --> [*]
    
    note right of NotSent: No serial number yet
    note right of Pending: Waiting for pickup
    note right of Delivered: Customer received
```

---

## 8. Plugin Activation Flow

```mermaid
flowchart TD
    A[Plugin Activated] --> B{WooCommerce Active?}
    B -->|No| C[Show Admin Notice]
    C --> D[Disable Plugin Features]
    B -->|Yes| E[Check PHP Version]
    E --> F{PHP >= 7.4?}
    F -->|No| G[Show Compatibility Error]
    F -->|Yes| H[Create Database Tables]
    H --> I[Set Default Options]
    I --> J[Schedule Cron Jobs]
    J --> K[Plugin Ready]
    K --> L[Redirect to Settings]
```

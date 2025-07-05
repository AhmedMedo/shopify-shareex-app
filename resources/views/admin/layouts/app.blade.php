<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Admin Panel - @yield('title')</title>

    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">

    <!-- DataTables CSS -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.5.0/css/responsive.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.2/css/buttons.bootstrap5.min.css">

    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">

    <style>
        .navbar-brand {
            font-weight: 600;
        }
        .sidebar {
            min-height: calc(100vh - 56px);
            background: #f8f9fa;
            border-right: 1px solid #dee2e6;
        }
        .sidebar .nav-link {
            color: #333;
            border-radius: 4px;
            margin-bottom: 2px;
        }
        .sidebar .nav-link:hover, .sidebar .nav-link.active {
            background: #e9ecef;
            color: #0d6efd;
        }
        .sidebar .nav-link i {
            margin-right: 8px;
        }
        .main-content {
            padding: 20px;
        }
        /* Add to your stylesheet */
        .city-select {
            transition: all 0.2s ease;
            max-width: 150px;
        }

        .save-city-btn, .change-city-btn, .cancel-change-btn {
            transition: all 0.2s ease;
            line-height: 1;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .save-city-btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }

        /* Make table cells a bit more compact */
        td {
            padding: 0.5rem !important;
            vertical-align: middle !important;
        }

        /* DataTables custom styling */
        .dataTables_wrapper .dataTables_length select {
            width: auto;
            display: inline-block;
        }

        .dataTables_wrapper .dataTables_filter input {
            width: auto;
            display: inline-block;
        }

        .dataTables_wrapper .dataTables_info {
            padding-top: 0.85em;
        }

        .dataTables_wrapper .dataTables_paginate .paginate_button {
            padding: 0.375rem 0.75rem;
            margin-left: 2px;
            border: 1px solid #dee2e6;
            background-color: #fff;
            color: #0d6efd;
        }

        .dataTables_wrapper .dataTables_paginate .paginate_button:hover {
            background-color: #e9ecef;
            border-color: #dee2e6;
            color: #0a58ca;
        }

        .dataTables_wrapper .dataTables_paginate .paginate_button.current {
            background-color: #0d6efd;
            border-color: #0d6efd;
            color: #fff;
        }

        /* Tab styling improvements */
        .nav-tabs .nav-link {
            border: none;
            border-bottom: 2px solid transparent;
            color: #6c757d;
            font-weight: 500;
        }

        .nav-tabs .nav-link.active {
            border-bottom-color: #0d6efd;
            color: #0d6efd;
            background: none;
        }

        .nav-tabs .nav-link:hover {
            border-bottom-color: #0d6efd;
            color: #0d6efd;
        }

        /* Badge improvements */
        .badge {
            font-size: 0.75em;
        }

        /* Button group improvements */
        .btn-group .btn {
            border-radius: 0.375rem;
        }

        .btn-group .btn:first-child {
            border-top-left-radius: 0.375rem;
            border-bottom-left-radius: 0.375rem;
        }

        .btn-group .btn:last-child {
            border-top-right-radius: 0.375rem;
            border-bottom-right-radius: 0.375rem;
        }
    </style>

    @stack('styles')
</head>
<body>
<!-- Navbar -->
<nav class="navbar navbar-expand-lg navbar-dark bg-primary">
    <div class="container-fluid">
        <a class="navbar-brand" href="{{ route('admin.home') }}">Admin Panel</a>

        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarSupportedContent">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="navbarSupportedContent">
            <ul class="navbar-nav ms-auto mb-2 mb-lg-0">
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                        <i class="bi bi-person-circle"></i> Admin
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li>
                            <form method="POST" action="{{ route('admin.logout') }}">
                                @csrf
                                <button type="submit" class="dropdown-item">
                                    <i class="bi bi-box-arrow-right"></i> Logout
                                </button>
                            </form>
                        </li>
                    </ul>
                </li>
            </ul>
        </div>
    </div>
</nav>

<!-- Main Content -->
<div class="container-fluid">
    <div class="row">
        <!-- Sidebar -->
        <div class="col-md-3 col-lg-2 d-md-block sidebar collapse">
            <div class="position-sticky pt-3">
                <ul class="nav flex-column">
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('admin.home') ? 'active' : '' }}" href="{{ route('admin.home') }}">
                            <i class="bi bi-speedometer2"></i> Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#">
                            <i class="bi bi-cart"></i> Orders
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#">
                            <i class="bi bi-people"></i> Customers
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#">
                            <i class="bi bi-gear"></i> Settings
                        </a>
                    </li>
                </ul>
            </div>
        </div>

        <!-- Main Content Area -->
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 main-content">
            @if(session('success'))
                <div class="alert alert-success alert-dismissible fade show mt-3">
                    {{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            @if($errors->any())
                <div class="alert alert-danger alert-dismissible fade show mt-3">
                    <ul class="mb-0">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            @yield('content')
        </main>
    </div>
</div>

<!-- Bootstrap JS Bundle with Popper -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<!-- jQuery (required for DataTables) -->
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>

<!-- DataTables JS -->
<script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.datatables.net/responsive/2.5.0/js/dataTables.responsive.min.js"></script>
<script src="https://cdn.datatables.net/responsive/2.5.0/js/responsive.bootstrap5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/dataTables.buttons.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.bootstrap5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.html5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.print.min.js"></script>

@stack('scripts')
</body>
</html>

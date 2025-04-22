<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'SmartMailer Dashboard')</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        :root {
            --primary-color: #0a66c2;
            --secondary-color: #0044a9;
            --success-color: #00a870;
            --warning-color: #f59e0b;
            --danger-color: #dc2626;
            --info-color: #3b82f6;
            --dark-color: #111827;
            --light-color: #f8fafc;
            --border-color: #e5e7eb;
            --border-radius: 6px;
            --card-shadow: 0 1px 3px rgba(0,0,0,0.1), 0 1px 2px rgba(0,0,0,0.06);
        }

        body {
            background-color: var(--light-color);
            font-family: 'Inter', sans-serif;
            color: var(--dark-color);
        }

        .navbar {
            background: var(--dark-color);
            padding: 0.75rem 0;
            box-shadow: 0 1px 2px rgba(0,0,0,0.1);
        }

        .navbar-brand {
            font-weight: 600;
            letter-spacing: -0.025em;
        }

        .card {
            border: 1px solid var(--border-color);
            border-radius: var(--border-radius);
            box-shadow: var(--card-shadow);
            transition: transform 0.2s ease, box-shadow 0.2s ease;
            background: white;
        }

        .card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }

        .stats-card {
            padding: 1.25rem;
        }

        .stats-icon {
            width: 42px;
            height: 42px;
            border-radius: var(--border-radius);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.25rem;
            margin-bottom: 1rem;
            background: var(--light-color);
            color: var(--primary-color);
        }

        .form-control, .form-select {
            border-radius: var(--border-radius);
            padding: 0.625rem 0.875rem;
            border: 1px solid var(--border-color);
            font-size: 0.95rem;
        }

        .form-control:focus, .form-select:focus {
            box-shadow: 0 0 0 2px rgba(10, 102, 194, 0.1);
            border-color: var(--primary-color);
        }

        .btn {
            padding: 0.625rem 1.25rem;
            border-radius: var(--border-radius);
            font-weight: 500;
            font-size: 0.95rem;
            transition: all 0.2s ease;
        }

        .btn-primary {
            background: var(--primary-color);
            border: none;
        }

        .btn-primary:hover {
            background: var(--secondary-color);
            transform: translateY(-1px);
        }

        .badge {
            padding: 0.375rem 0.75rem;
            border-radius: calc(var(--border-radius) - 2px);
            font-weight: 500;
            font-size: 0.85rem;
        }

        .table {
            vertical-align: middle;
        }

        .table thead th {
            background: var(--light-color);
            border-bottom: 2px solid var(--border-color);
            padding: 0.875rem;
            font-weight: 600;
            color: var(--dark-color);
            font-size: 0.95rem;
        }

        .table tbody td {
            padding: 0.875rem;
            border-bottom: 1px solid var(--border-color);
            color: #4b5563;
        }

        .alert {
            border-radius: var(--border-radius);
            border: 1px solid transparent;
            padding: 0.875rem 1.25rem;
        }

        .alert-success {
            background-color: #ecfdf5;
            border-color: #a7f3d0;
            color: #065f46;
        }

        .alert-danger {
            background-color: #fef2f2;
            border-color: #fecaca;
            color: #991b1b;
        }

        .animate-fade-in {
            animation: fadeIn 0.5s ease forwards;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        /* Custom Scrollbar */
        ::-webkit-scrollbar {
            width: 6px;
            height: 6px;
        }

        ::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 3px;
        }

        ::-webkit-scrollbar-thumb {
            background: #c1c1c1;
            border-radius: 3px;
        }

        ::-webkit-scrollbar-thumb:hover {
            background: #a8a8a8;
        }

        /* Loading Spinner */
        .loading-spinner {
            width: 40px;
            height: 40px;
            border: 3px solid #f3f3f3;
            border-top: 3px solid var(--primary-color);
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        /* Responsive Adjustments */
        @media (max-width: 768px) {
            .stats-card {
                margin-bottom: 1rem;
            }

            .table-responsive {
                border: 0;
            }

            .btn {
                padding: 0.5rem 1rem;
            }
        }
    </style>
    @stack('styles')
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark mb-4">
        <div class="container">
            <a class="navbar-brand d-flex align-items-center" href="{{ route('smartmailer.dashboard') }}">
                <i class="bi bi-envelope-fill me-2"></i>
                <span>SmartMailer</span>
            </a>
            <div class="d-flex align-items-center text-white">
                <i class="bi bi-clock me-2"></i>
                <span id="currentTime"></span>
            </div>
        </div>
    </nav>

    <div class="container">
        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        @if(session('error'))
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                {{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        @yield('content')
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
    <script>
        // Update current time
        function updateTime() {
            const now = new Date();
            document.getElementById('currentTime').textContent = 
                now.toLocaleTimeString('en-US', { 
                    hour12: false, 
                    hour: '2-digit', 
                    minute: '2-digit', 
                    second: '2-digit' 
                });
        }
        setInterval(updateTime, 1000);
        updateTime();

        // Initialize tooltips
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
        var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl)
        });
    </script>
    @stack('scripts')
</body>
</html>
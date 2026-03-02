<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Fabricate ERP')</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
        }
    </style>
    @stack('styles')
</head>
<body class="bg-gray-50 min-h-screen">
    <!-- Header -->
    <header class="bg-white shadow-sm">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4 flex items-center justify-between">
            <div class="flex items-center space-x-2">
                <div class="w-10 h-10 bg-blue-600 rounded-lg flex items-center justify-center">
                    <i class="fas fa-industry text-white text-xl"></i>
                </div>
                <span class="text-xl font-semibold text-gray-900">Fabricate ERP</span>
            </div>
            <div class="flex items-center space-x-6">
                <a href="/" class="text-gray-600 hover:text-gray-900">Features</a>
                <a href="/" class="text-gray-600 hover:text-gray-900">Solutions</a>
                <a href="/" class="text-gray-600 hover:text-gray-900">Pricing</a>
                <a href="/login" class="px-4 py-2 text-blue-600 hover:text-blue-700 font-medium">Login</a>
                <a href="/register" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 font-medium">Get Started</a>
            </div>
        </div>
    </header>

    @yield('content')

    <!-- Footer -->
    <footer class="bg-white border-t border-gray-200 py-4 mt-auto">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 flex justify-between items-center text-sm text-gray-600">
            <span>© 2024 Fabricate ERP Systems. All rights reserved.</span>
            <div class="flex space-x-6">
                <a href="#" class="hover:text-gray-900">Privacy Policy</a>
                <a href="#" class="hover:text-gray-900">Terms of Service</a>
                <a href="#" class="hover:text-gray-900">Contact Support</a>
            </div>
        </div>
    </footer>

    @stack('scripts')
</body>
</html>

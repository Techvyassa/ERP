<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>403 - Access Denied | Nexus ERP</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Outfit', sans-serif; }
        .glass {
            background: rgba(255, 255, 255, 0.7);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.3);
        }
        .gradient-bg {
            background: radial-gradient(circle at top left, #f8fafc, #e2e8f0);
            min-height: 100vh;
        }
    </style>
</head>
<body class="gradient-bg flex items-center justify-center p-6">
    <div class="max-w-md w-full text-center">
        <!-- Icon -->
        <div class="mb-8 relative inline-block">
            <div class="absolute inset-0 bg-red-100 rounded-full blur-2xl opacity-50 animate-pulse"></div>
            <div class="relative w-24 h-24 bg-white rounded-3xl shadow-xl flex items-center justify-center border border-red-50 mx-auto">
                <svg class="w-12 h-12 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                </svg>
            </div>
        </div>

        <!-- Text content -->
        <h1 class="text-4xl font-bold text-slate-900 mb-4 tracking-tight">Access Restricted</h1>
        <p class="text-slate-600 mb-8 leading-relaxed">
            Oops! It looks like you don't have the necessary permissions to access this module.
            @if(isset($message))
                <span class="block mt-2 font-medium text-red-500">{{ $message }}</span>
            @endif
        </p>

        <!-- Actions -->
        <div class="grid grid-cols-1 gap-3">
            <button onclick="window.history.back()" class="w-full py-4 bg-slate-900 text-white font-semibold rounded-2xl shadow-lg hover:shadow-slate-200 hover:bg-slate-800 transition-all active:scale-95">
                Go Back
            </button>
            <a href="/" class="w-full py-4 bg-white text-slate-900 font-semibold rounded-2xl border border-slate-200 hover:bg-slate-50 transition-all text-center">
                Return to Dashboard
            </a>
        </div>

        <p class="mt-12 text-sm text-slate-400">
            Error Code: <span class="font-mono">{{ $code ?? 'PERMISSION_DENIED' }}</span><br>
            Reference: {{ request()->getRequestUri() }}
        </p>
    </div>
</body>
</html>

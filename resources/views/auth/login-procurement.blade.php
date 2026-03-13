<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Procurement Login - Nexus ERP</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet" />
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet" />
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        "primary": "#193261",
                    },
                    fontFamily: {
                        "display": ["Inter", "sans-serif"]
                    },
                },
            },
        }
    </script>
</head>
<body class="bg-slate-50 min-h-screen flex items-center justify-center font-display p-4">
    <div class="w-full max-w-[1000px] grid grid-cols-1 md:grid-cols-2 bg-white rounded-2xl shadow-2xl overflow-hidden border border-slate-200">
        <!-- Brand Side -->
        <div class="hidden md:flex flex-col justify-between p-12 bg-primary text-white relative overflow-hidden">
            <div class="relative z-10">
                <div class="flex items-center gap-3 mb-12">
                    <div class="bg-white/20 p-2 rounded-lg backdrop-blur-sm">
                        <span class="material-symbols-outlined text-white text-3xl">shopping_cart</span>
                    </div>
                    <h2 class="text-2xl font-bold tracking-tight">Nexus Procurement</h2>
                </div>
                <h1 class="text-4xl font-extrabold leading-tight mb-6">Streamline Your Supply Chain Lifecycle</h1>
                <p class="text-blue-100 text-lg">Access vendor management, purchase orders, and inward tracking in one unified portal.</p>
            </div>
            
            <div class="relative z-10">
                <div class="flex items-center gap-4 p-4 bg-white/10 rounded-xl backdrop-blur-md border border-white/20">
                    <div class="size-10 rounded-full bg-emerald-400 flex items-center justify-center text-primary">
                        <span class="material-symbols-outlined">verified</span>
                    </div>
                    <div>
                        <p class="text-sm font-bold text-white">Authorized Access Only</p>
                        <p class="text-xs text-blue-100 uppercase tracking-widest font-semibold">Procurement Executive Portal</p>
                    </div>
                </div>
            </div>

            <!-- Abstract Background Decor -->
            <div class="absolute -bottom-24 -right-24 size-96 bg-blue-500/20 rounded-full blur-3xl"></div>
            <div class="absolute -top-24 -left-24 size-96 bg-white/10 rounded-full blur-3xl"></div>
        </div>

        <!-- Login Side -->
        <div class="p-8 md:p-12 flex flex-col justify-center">
            <div class="mb-10 text-center md:text-left">
                <h3 class="text-3xl font-bold text-slate-900 mb-2">Welcome Back</h3>
                <p class="text-slate-500 font-medium">Please enter your credentials to continue</p>
            </div>

            <form id="loginForm" class="space-y-5">
                <div>
                    <label for="email" class="block text-sm font-bold text-slate-700 mb-2">Work Email</label>
                    <div class="relative">
                        <span class="material-symbols-outlined absolute left-4 top-1/2 -translate-y-1/2 text-slate-400">mail</span>
                        <input 
                            type="email" 
                            id="email" 
                            name="email" 
                            required
                            class="w-full pl-12 pr-4 py-3 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-primary/20 focus:border-primary transition-all"
                            placeholder="alex@company.com">
                    </div>
                </div>

                <div>
                    <label for="password" class="block text-sm font-bold text-slate-700 mb-2">Password</label>
                    <div class="relative">
                        <span class="material-symbols-outlined absolute left-4 top-1/2 -translate-y-1/2 text-slate-400">lock</span>
                        <input 
                            type="password" 
                            id="password" 
                            name="password" 
                            required
                            class="w-full pl-12 pr-12 py-3 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-primary/20 focus:border-primary transition-all"
                            placeholder="••••••••">
                        <button type="button" class="absolute right-4 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600">
                            <span class="material-symbols-outlined">visibility</span>
                        </button>
                    </div>
                </div>

                <div class="flex items-center justify-between">
                    <label class="flex items-center gap-2 cursor-pointer group">
                        <input type="checkbox" class="size-4 rounded border-slate-300 text-primary focus:ring-primary/20 group-hover:border-primary transition-colors">
                        <span class="text-sm font-medium text-slate-600 group-hover:text-primary transition-colors">Remember me</span>
                    </label>
                    <a href="#" class="text-sm font-bold text-primary hover:underline">Forgot password?</a>
                </div>

                <button 
                    type="submit" 
                    id="submitBtn"
                    class="w-full py-4 bg-primary text-white font-bold rounded-xl hover:bg-primary/95 transition-all shadow-lg shadow-primary/20 flex items-center justify-center gap-2">
                    Sign In to Procurement
                    <span class="material-symbols-outlined">arrow_forward</span>
                </button>
            </form>

    <script>
        document.getElementById('loginForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            const submitBtn = document.getElementById('submitBtn');
            const email = document.getElementById('email').value;
            const password = document.getElementById('password').value;

            submitBtn.disabled = true;
            submitBtn.innerHTML = '<span class="material-symbols-outlined animate-spin text-sm">progress_activity</span> Authenticating...';

            try {
                const response = await fetch('/api/v1/auth/login', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    credentials: 'include',
                    body: JSON.stringify({ email, password })
                });

                const data = await response.json();

                if (response.ok && data.success) {
                    // Store tokens etc (if using client-side storage)
                    localStorage.setItem('user', JSON.stringify(data.data.user));
                    localStorage.setItem('access_token', data.data.access_token);
                    
                    // Redirect to specialized dashboard
                    window.location.href = '/procurement/dashboard';
                } else {
                    alert(data.message || 'Authentication failed');
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = 'Sign In to Procurement <span class="material-symbols-outlined">arrow_forward</span>';
                }
            } catch (error) {
                console.error('Error:', error);
                alert('An error occurred. Please try again.');
                submitBtn.disabled = false;
            }
        });
    </script>
</body>
</html>

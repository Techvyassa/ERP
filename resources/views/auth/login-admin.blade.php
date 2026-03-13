<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Administrator Login - Nexus ERP</title>
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
<body class="bg-primary min-h-screen flex items-center justify-center font-display p-4">
    <!-- Abstract Background Decor -->
    <div class="fixed inset-0 overflow-hidden pointer-events-none">
        <div class="absolute -top-[10%] -left-[10%] size-[60%] bg-blue-600/20 rounded-full blur-[120px]"></div>
        <div class="absolute -bottom-[10%] -right-[10%] size-[60%] bg-indigo-600/20 rounded-full blur-[120px]"></div>
    </div>

    <div class="w-full max-w-md relative z-10">
        <div class="bg-white/10 backdrop-blur-2xl border border-white/20 p-8 rounded-[2rem] shadow-2xl">
            <div class="text-center mb-10">
                <div class="inline-flex items-center justify-center size-16 bg-white rounded-2xl shadow-xl mb-6">
                    <span class="material-symbols-outlined text-primary text-4xl font-bold">shield_person</span>
                </div>
                <h1 class="text-3xl font-black text-white tracking-tight">Admin Portal</h1>
                <p class="text-blue-200 mt-2 font-medium">Enterprise Management Suite</p>
            </div>

            <form id="loginForm" class="space-y-6">
                <div>
                    <label for="email" class="block text-xs font-black text-blue-200 uppercase tracking-widest mb-2 ml-1">Admin Email</label>
                    <input 
                        type="email" 
                        id="email" 
                        name="email" 
                        required
                        class="w-full px-6 py-4 bg-white/5 border border-white/10 rounded-2xl text-white placeholder-blue-300/50 focus:ring-2 focus:ring-white/20 focus:bg-white/10 transition-all outline-none"
                        placeholder="admin@nexus.com">
                </div>

                <div>
                    <label for="password" class="block text-xs font-black text-blue-200 uppercase tracking-widest mb-2 ml-1">Security Token</label>
                    <input 
                        type="password" 
                        id="password" 
                        name="password" 
                        required
                        class="w-full px-6 py-4 bg-white/5 border border-white/10 rounded-2xl text-white placeholder-blue-300/50 focus:ring-2 focus:ring-white/20 focus:bg-white/10 transition-all outline-none"
                        placeholder="••••••••">
                </div>

                <div class="flex items-center justify-end">
                    <a href="#" class="text-xs font-bold text-blue-200 hover:text-white transition-colors">Request Token Reset</a>
                </div>

                <button 
                    type="submit" 
                    id="submitBtn"
                    class="w-full py-4 bg-white text-primary font-black rounded-2xl hover:bg-blue-50 transition-all shadow-xl flex items-center justify-center gap-2">
                    Initialize System Access
                    <span class="material-symbols-outlined text-xl">verified_user</span>
                </button>
            </form>

    <script>
        document.getElementById('loginForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            const submitBtn = document.getElementById('submitBtn');
            const email = document.getElementById('email').value;
            const password = document.getElementById('password').value;

            submitBtn.disabled = true;
            submitBtn.innerHTML = '<span class="material-symbols-outlined animate-spin text-sm">progress_activity</span> Initializing...';

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
                    localStorage.setItem('user', JSON.stringify(data.data.user));
                    localStorage.setItem('access_token', data.data.access_token);
                    // Admin goes to control panel
                    window.location.href = '/control/dashboard';
                } else {
                    alert(data.message || 'Authentication failed');
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = 'Initialize System Access <span class="material-symbols-outlined text-xl">verified_user</span>';
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

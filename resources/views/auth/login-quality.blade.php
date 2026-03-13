<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Quality Assurance Login - Nexus ERP</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet" />
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet" />
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        "primary": "#193261",
                        "qc": "#0ea5e9",
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
        <div class="hidden md:flex flex-col justify-between p-12 bg-white text-slate-900 relative overflow-hidden border-r border-slate-100">
            <div class="relative z-10">
                <div class="flex items-center gap-3 mb-12">
                    <div class="bg-qc p-2 rounded-lg">
                        <span class="material-symbols-outlined text-white text-3xl">biotech</span>
                    </div>
                    <h2 class="text-2xl font-bold tracking-tight text-primary">Nexus Quality</h2>
                </div>
                <h1 class="text-4xl font-extrabold leading-tight mb-6 text-slate-800">Precision Standards for Industrial Excellence</h1>
                <p class="text-slate-500 text-lg font-medium">Record inspections, manage lab tests, and make critical usage decisions with ease.</p>
            </div>
            
            <div class="relative z-10">
                <div class="flex items-center gap-4 p-4 bg-qc/5 rounded-xl border border-qc/20">
                    <div class="size-10 rounded-full bg-qc flex items-center justify-center text-white">
                        <span class="material-symbols-outlined">fact_check</span>
                    </div>
                    <div>
                        <p class="text-sm font-bold text-slate-900">QA Terminal</p>
                        <p class="text-xs text-slate-500 uppercase tracking-widest font-semibold">QC Technician & Manager Portal</p>
                    </div>
                </div>
            </div>

            <!-- Decorative Elements -->
            <div class="absolute top-0 right-0 w-64 h-64 bg-qc/5 rounded-full -mr-32 -mt-32"></div>
            <div class="absolute bottom-0 left-0 w-32 h-32 bg-primary/5 rounded-full -ml-16 -mb-16"></div>
        </div>

        <!-- Login Side -->
        <div class="p-8 md:p-12 flex flex-col justify-center bg-slate-50/30">
            <div class="mb-10 text-center md:text-left">
                <div class="inline-flex items-center gap-2 px-3 py-1 bg-qc/10 text-qc rounded-full text-xs font-bold uppercase tracking-widest mb-4">
                    Secure Lab Access
                </div>
                <h3 class="text-3xl font-bold text-slate-900 mb-2">Quality Login</h3>
                <p class="text-slate-500 font-medium">Enter your analyst credentials</p>
            </div>

            <form id="loginForm" class="space-y-5">
                <div>
                    <label for="email" class="block text-sm font-bold text-slate-700 mb-2">Analyst Email</label>
                    <div class="relative">
                        <input 
                            type="email" 
                            id="email" 
                            name="email" 
                            required
                            class="w-full px-4 py-3 bg-white border border-slate-200 rounded-xl focus:ring-2 focus:ring-qc/20 focus:border-qc transition-all shadow-sm"
                            placeholder="analyst@nexus.com">
                    </div>
                </div>

                <div>
                    <label for="password" class="block text-sm font-bold text-slate-700 mb-2">Access Key Code</label>
                    <div class="relative">
                        <input 
                            type="password" 
                            id="password" 
                            name="password" 
                            required
                            class="w-full px-4 py-3 bg-white border border-slate-200 rounded-xl focus:ring-2 focus:ring-qc/20 focus:border-qc transition-all shadow-sm"
                            placeholder="••••••••">
                    </div>
                </div>

                <button 
                    type="submit" 
                    id="submitBtn"
                    class="w-full py-4 bg-qc text-white font-bold rounded-xl hover:bg-qc/90 transition-all shadow-lg shadow-qc/20 flex items-center justify-center gap-2">
                    Enter Quality Suite
                    <span class="material-symbols-outlined">science</span>
                </button>
            </form>

    <script>
        document.getElementById('loginForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            const submitBtn = document.getElementById('submitBtn');
            const email = document.getElementById('email').value;
            const password = document.getElementById('password').value;

            submitBtn.disabled = true;
            submitBtn.innerHTML = '<span class="material-symbols-outlined animate-spin text-sm">progress_activity</span> Analyzing...';

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
                    window.location.href = '/quality/dashboard';
                } else {
                    alert(data.message || 'Authentication failed');
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = 'Enter Quality Suite <span class="material-symbols-outlined">science</span>';
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

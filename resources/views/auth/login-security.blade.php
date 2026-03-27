<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Security Login - Nexus ERP</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet" />
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: { "primary": "#193261" },
                    fontFamily: { "display": ["Inter", "sans-serif"] },
                },
            },
        }
    </script>
</head>
<body class="bg-slate-50 min-h-screen flex items-center justify-center font-display p-4">
    <div class="w-full max-w-md">
        <div class="bg-white rounded-2xl shadow-lg border border-slate-200 p-8">
            <div class="text-center mb-8">
                <h1 class="text-2xl font-bold text-slate-900 mb-2">Security Portal</h1>
                <p class="text-slate-600">Sign in to continue</p>
            </div>

            <div id="errorMsg" class="hidden mb-4 p-3 bg-red-50 border border-red-200 text-red-700 text-sm rounded-lg"></div>

            <form id="loginForm" class="space-y-5">
                <div>
                    <label for="email" class="block text-sm font-semibold text-slate-700 mb-2">Email Address</label>
                    <input
                        type="email"
                        id="email"
                        name="email"
                        required
                        class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-lg focus:ring-2 focus:ring-primary/20 focus:border-primary transition-all outline-none"
                        placeholder="your.email@company.com">
                </div>

                <div>
                    <label for="password" class="block text-sm font-semibold text-slate-700 mb-2">Password</label>
                    <input
                        type="password"
                        id="password"
                        type="password" 
                        id="password" 
                        name="password" 
                        required
                        class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-lg focus:ring-2 focus:ring-primary/20 focus:border-primary transition-all outline-none"
                        placeholder="••••••••">
                </div>

                        <input type="checkbox" class="w-4 h-4 rounded border-slate-300 text-primary focus:ring-primary/20">
                        <span class="text-sm text-slate-600">Remember me</span>
                    </label>
                    <a href="/forgot-password" class="text-sm font-semibold text-primary hover:underline">Forgot password?</a>
                </div>

                <button
                    type="submit"
                    id="submitBtn"
                    class="w-full py-3 bg-primary text-white font-semibold rounded-lg hover:bg-primary/90 transition-all">
                    Sign In
                </button>
            </form>
        </div>
    </div>

    <script>
        document.getElementById('loginForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            const submitBtn = document.getElementById('submitBtn');
            const errorMsg = document.getElementById('errorMsg');
            const email = document.getElementById('email').value;
            const password = document.getElementById('password').value;

            submitBtn.disabled = true;
            submitBtn.textContent = 'Signing in...';
            errorMsg.classList.add('hidden');

            try {
                const response = await fetch('/api/v1/auth/login', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    credentials: 'include',
                    body: JSON.stringify({ email, password, org_slug: '{{ $orgSlug }}', remember_me: document.querySelector('input[type="checkbox"]')?.checked ?? false })
                });

                const data = await response.json();

                if (response.ok && data.success) {
                    localStorage.setItem('user', JSON.stringify(data.data.user));
                    localStorage.setItem('access_token', data.data.access_token);

                    const orgSlug = '{{ $orgSlug }}';
                    localStorage.setItem('login_portal_url', `/org/${orgSlug}/security/login`);
                    window.location.href = `/org/${orgSlug}/security/dashboard`;
                } else {
                    errorMsg.textContent = data.message || 'Authentication failed. Please check your credentials.';
                    errorMsg.classList.remove('hidden');
                    submitBtn.disabled = false;
                    submitBtn.textContent = 'Sign In';
                }
            } catch (error) {
                console.error('Login error:', error);
                errorMsg.textContent = 'An error occurred. Please try again.';
                errorMsg.classList.remove('hidden');
                submitBtn.disabled = false;
                submitBtn.textContent = 'Sign In';
            }
        });
    </script>
</body>
</html>

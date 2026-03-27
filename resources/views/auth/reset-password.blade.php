<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Reset Password - Zap ERP</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet" />
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet" />
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: { extend: { colors: { "primary": "#193261" }, fontFamily: { "display": ["Inter", "sans-serif"] } } }
        }
    </script>
    <style>body { font-family: 'Inter', sans-serif; }</style>
</head>
<body class="bg-gray-50 min-h-screen flex flex-col font-display">
    <header class="w-full border-b border-gray-200 bg-white shadow-sm">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4 flex items-center">
            <a href="/" class="flex items-center gap-3">
                <div class="bg-primary p-1.5 rounded-lg flex items-center justify-center">
                    <span class="material-symbols-outlined text-white text-2xl">precision_manufacturing</span>
                </div>
                <h2 class="text-gray-900 text-xl font-bold tracking-tight">Zap ERP</h2>
            </a>
        </div>
    </header>

    <div class="flex-1 flex items-center justify-center p-4">
        <div class="w-full max-w-md">
            <div class="bg-white rounded-2xl shadow-lg border border-gray-200 p-8">
                <div id="formView">
                    <div class="flex justify-center mb-6">
                        <div class="bg-primary/10 p-4 rounded-full">
                            <span class="material-symbols-outlined text-primary text-4xl">lock</span>
                        </div>
                    </div>
                    <h1 class="text-2xl font-bold text-gray-900 text-center mb-2">Set New Password</h1>
                    <p class="text-gray-600 text-center mb-8 text-sm">Choose a strong password for your account.</p>

                    <form id="resetForm" class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">New Password</label>
                            <div class="relative">
                                <input type="password" id="password" name="password" required minlength="8"
                                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent pr-12"
                                    placeholder="Min. 8 characters">
                                <button type="button" onclick="togglePwd('password','icon1')"
                                    class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600">
                                    <span class="material-symbols-outlined text-xl" id="icon1">visibility</span>
                                </button>
                            </div>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Confirm Password</label>
                            <div class="relative">
                                <input type="password" id="password_confirmation" name="password_confirmation" required
                                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent pr-12"
                                    placeholder="Repeat password">
                                <button type="button" onclick="togglePwd('password_confirmation','icon2')"
                                    class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600">
                                    <span class="material-symbols-outlined text-xl" id="icon2">visibility</span>
                                </button>
                            </div>
                        </div>

                        <div id="errorMessage" class="hidden bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg text-sm"></div>

                        <button type="submit" id="submitBtn"
                            class="w-full px-6 py-3 bg-primary text-white font-bold rounded-lg hover:bg-primary/90 transition-colors">
                            Reset Password
                        </button>
                    </form>
                </div>

                <div id="successView" class="hidden text-center">
                    <div class="flex justify-center mb-6">
                        <div class="bg-green-100 p-4 rounded-full">
                            <span class="material-symbols-outlined text-green-600 text-4xl">check_circle</span>
                        </div>
                    </div>
                    <h2 class="text-xl font-bold text-gray-900 mb-3">Password Reset!</h2>
                    <p class="text-gray-600 text-sm mb-6">Your password has been updated. You can now sign in with your new password.</p>
                    <a href="/login" class="inline-block px-6 py-3 bg-primary text-white font-bold rounded-lg hover:bg-primary/90 transition-colors">
                        Sign In
                    </a>
                </div>

                <div id="invalidView" class="hidden text-center">
                    <div class="flex justify-center mb-6">
                        <div class="bg-red-100 p-4 rounded-full">
                            <span class="material-symbols-outlined text-red-600 text-4xl">error</span>
                        </div>
                    </div>
                    <h2 class="text-xl font-bold text-gray-900 mb-3">Invalid or Expired Link</h2>
                    <p class="text-gray-600 text-sm mb-6">This password reset link is invalid or has expired.</p>
                    <a href="/forgot-password" class="inline-block px-6 py-3 bg-primary text-white font-bold rounded-lg hover:bg-primary/90 transition-colors">
                        Request New Link
                    </a>
                </div>
            </div>
        </div>
    </div>

    <script>
        function togglePwd(id, iconId) {
            const input = document.getElementById(id);
            const icon  = document.getElementById(iconId);
            input.type  = input.type === 'password' ? 'text' : 'password';
            icon.textContent = input.type === 'password' ? 'visibility' : 'visibility_off';
        }

        const params = new URLSearchParams(window.location.search);
        const token  = params.get('token');
        const email  = params.get('email');

        if (!token || !email) {
            document.getElementById('formView').classList.add('hidden');
            document.getElementById('invalidView').classList.remove('hidden');
        }

        document.getElementById('resetForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            const submitBtn    = document.getElementById('submitBtn');
            const errorMessage = document.getElementById('errorMessage');
            const password     = document.getElementById('password').value;
            const confirmation = document.getElementById('password_confirmation').value;

            errorMessage.classList.add('hidden');

            if (password !== confirmation) {
                errorMessage.textContent = 'Passwords do not match.';
                errorMessage.classList.remove('hidden');
                return;
            }

            submitBtn.disabled = true;
            submitBtn.innerHTML = '<span class="material-symbols-outlined animate-spin text-sm mr-1">progress_activity</span>Resetting...';

            try {
                const response = await fetch('/api/v1/auth/reset-password', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify({ email, token, password, password_confirmation: confirmation })
                });

                const data = await response.json();

                if (data.success) {
                    document.getElementById('formView').classList.add('hidden');
                    document.getElementById('successView').classList.remove('hidden');
                } else {
                    if (data.error?.code === 'TOKEN_EXPIRED' || data.error?.code === 'INVALID_TOKEN') {
                        document.getElementById('formView').classList.add('hidden');
                        document.getElementById('invalidView').classList.remove('hidden');
                    } else {
                        errorMessage.textContent = data.message || 'Something went wrong. Please try again.';
                        errorMessage.classList.remove('hidden');
                    }
                }
            } catch (error) {
                errorMessage.textContent = 'An error occurred. Please try again.';
                errorMessage.classList.remove('hidden');
            } finally {
                submitBtn.disabled = false;
                submitBtn.textContent = 'Reset Password';
            }
        });
    </script>
</body>
</html>

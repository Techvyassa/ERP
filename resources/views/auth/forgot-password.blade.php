<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Forgot Password - Zap ERP</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet" />
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet" />
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
    <style>body { font-family: 'Inter', sans-serif; }</style>
</head>
<body class="bg-gray-50 min-h-screen flex flex-col font-display">
    <header class="w-full border-b border-gray-200 bg-white shadow-sm">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4 flex items-center justify-between">
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
                            <span class="material-symbols-outlined text-primary text-4xl">lock_reset</span>
                        </div>
                    </div>
                    <h1 class="text-2xl font-bold text-gray-900 text-center mb-2">Forgot Password?</h1>
                    <p class="text-gray-600 text-center mb-8 text-sm">Enter your email and we'll send you a reset link.</p>

                    <form id="forgotForm" class="space-y-4">
                        <div>
                            <label for="email" class="block text-sm font-medium text-gray-700 mb-2">Email Address</label>
                            <input type="email" id="email" name="email" required
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent"
                                placeholder="you@company.com">
                        </div>

                        <div id="errorMessage" class="hidden bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg text-sm"></div>

                        <button type="submit" id="submitBtn"
                            class="w-full px-6 py-3 bg-primary text-white font-bold rounded-lg hover:bg-primary/90 transition-colors">
                            Send Reset Link
                        </button>
                    </form>
                </div>

                <div id="successView" class="hidden text-center">
                    <div class="flex justify-center mb-6">
                        <div class="bg-green-100 p-4 rounded-full">
                            <span class="material-symbols-outlined text-green-600 text-4xl">mark_email_read</span>
                        </div>
                    </div>
                    <h2 class="text-xl font-bold text-gray-900 mb-3">Check Your Email</h2>
                    <p class="text-gray-600 text-sm mb-6">If an account with that email exists, we've sent a password reset link. It expires in 1 hour.</p>
                    <p class="text-gray-500 text-xs">Didn't receive it? Check your spam folder or <button onclick="showForm()" class="text-primary font-semibold hover:underline">try again</button>.</p>
                </div>

                <div class="mt-6 text-center">
                    <a href="/login" class="text-sm text-primary hover:text-primary/80 font-medium">← Back to Sign In</a>
                </div>
            </div>
        </div>
    </div>

    <script>
        function showForm() {
            document.getElementById('formView').classList.remove('hidden');
            document.getElementById('successView').classList.add('hidden');
        }

        document.getElementById('forgotForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            const submitBtn = document.getElementById('submitBtn');
            const errorMessage = document.getElementById('errorMessage');
            const email = document.getElementById('email').value;

            errorMessage.classList.add('hidden');
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<span class="material-symbols-outlined animate-spin text-sm mr-1">progress_activity</span>Sending...';

            try {
                const response = await fetch('/api/v1/auth/forgot-password', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify({ email })
                });

                const data = await response.json();

                if (data.success) {
                    document.getElementById('formView').classList.add('hidden');
                    document.getElementById('successView').classList.remove('hidden');
                } else {
                    errorMessage.textContent = data.message || 'Something went wrong. Please try again.';
                    errorMessage.classList.remove('hidden');
                }
            } catch (error) {
                errorMessage.textContent = 'An error occurred. Please try again.';
                errorMessage.classList.remove('hidden');
            } finally {
                submitBtn.disabled = false;
                submitBtn.textContent = 'Send Reset Link';
            }
        });
    </script>
</body>
</html>

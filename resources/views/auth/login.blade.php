<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Sign In - Zap ERP</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet" />
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet" />
    <script src="https://cdn.tailwindcss.com"></script>
    
    <!-- Firebase Configuration -->
    @include('components.firebase-config')
    
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
    
    <style>
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
        }
    </style>
</head>
<body class="bg-gray-50 min-h-screen flex flex-col font-display">
    <!-- Header -->
    <header class="w-full border-b border-gray-200 bg-white shadow-sm">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4 flex items-center justify-between">
            <a href="/" class="flex items-center gap-3">
                <div class="bg-primary p-1.5 rounded-lg flex items-center justify-center">
                    <span class="material-symbols-outlined text-white text-2xl">precision_manufacturing</span>
                </div>
                <h2 class="text-gray-900 text-xl font-bold tracking-tight">Zap ERP</h2>
            </a>
            <div class="flex items-center gap-6">
                <a href="/" class="text-gray-600 hover:text-primary text-sm font-medium transition-colors">Home</a>
                <a href="/pricing" class="text-gray-600 hover:text-primary text-sm font-medium transition-colors">Pricing</a>
                <a href="/pricing" class="px-4 py-2 bg-primary text-white rounded-lg text-sm font-bold hover:bg-primary/90 transition-all">Get Started</a>
            </div>
        </div>
    </header>

    <!-- Login Card -->
    <div class="flex-1 flex items-center justify-center p-4">
        <div class="w-full max-w-md">
            <div class="bg-white rounded-2xl shadow-lg border border-gray-200 p-8">
                <!-- Title -->
                <h1 class="text-3xl font-bold text-gray-900 text-center mb-2">Welcome Back</h1>
                <p class="text-gray-600 text-center mb-8">Sign in to your account to access your workspace</p>

            <!-- Session Error Message -->
            @if(session('error'))
            <div class="mb-6 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg">
                <div class="flex items-center">
                    <i class="fas fa-exclamation-circle mr-2"></i>
                    <span>{{ session('error') }}</span>
                </div>
            </div>
            @endif

                <!-- Google Sign In -->
                <button type="button" id="googleSignInBtn" class="w-full flex items-center justify-center space-x-3 px-6 py-3 bg-white border-2 border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition-colors mb-6">
                    <svg class="w-5 h-5" viewBox="0 0 24 24">
                        <path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/>
                        <path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
                        <path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"/>
                        <path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/>
                    </svg>
                    <span class="font-medium">Continue with Google</span>
                </button>

                <!-- Divider -->
                <div class="relative my-6">
                    <div class="absolute inset-0 flex items-center">
                        <div class="w-full border-t border-gray-300"></div>
                    </div>
                    <div class="relative flex justify-center text-sm">
                        <span class="px-4 bg-white text-gray-500 font-medium">OR CONTINUE WITH EMAIL</span>
                    </div>
                </div>

                <!-- Login Form -->
                <form id="loginForm" class="space-y-4">
                    <!-- Email Address -->
                    <div>
                        <label for="email" class="block text-sm font-medium text-gray-700 mb-2">Email Address</label>
                        <input 
                            type="email" 
                            id="email" 
                            name="email" 
                            required
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent"
                            placeholder="you@company.com">
                        <span class="text-xs text-red-600 hidden" id="email_error"></span>
                    </div>

                    <!-- Password -->
                    <div>
                        <label for="password" class="block text-sm font-medium text-gray-700 mb-2">Password</label>
                        <div class="relative">
                            <input 
                                type="password" 
                                id="password" 
                                name="password" 
                                required
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent pr-12"
                                placeholder="••••••••">
                            <button 
                                type="button" 
                                onclick="togglePassword()"
                                class="absolute right-3 top-1/2 transform -translate-y-1/2 text-gray-400 hover:text-gray-600">
                                <span class="material-symbols-outlined text-xl" id="toggleIcon">visibility</span>
                            </button>
                        </div>
                        <span class="text-xs text-red-600 hidden" id="password_error"></span>
                    </div>

                    <!-- Remember Me & Forgot Password -->
                    <div class="flex items-center justify-between">
                        <label class="flex items-center">
                            <input type="checkbox" name="remember" class="w-4 h-4 text-primary border-gray-300 rounded focus:ring-primary">
                            <span class="ml-2 text-sm text-gray-700">Keep me signed in</span>
                        </label>
                        <a href="/forgot-password" class="text-sm text-primary hover:text-primary/80 font-medium">Forgot password?</a>
                    </div>

                    <!-- Error Message -->
                    <div id="errorMessage" class="hidden bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg">
                        <div class="flex items-center">
                            <span class="material-symbols-outlined mr-2">error</span>
                            <span id="errorText"></span>
                        </div>
                    </div>

                    <!-- Submit Button -->
                    <button 
                        type="submit" 
                        id="submitBtn"
                        class="w-full px-6 py-3 bg-primary text-white font-bold rounded-lg hover:bg-primary/90 transition-colors shadow-lg shadow-primary/20">
                        Sign In
                    </button>
                </form>

                <!-- Sign Up Link -->
                <div class="mt-6 text-center">
                    <span class="text-sm text-gray-600">Don't have an account? </span>
                    <a href="/pricing" class="text-sm text-primary hover:text-primary/80 font-bold">Get Started</a>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="bg-white border-t border-gray-200 py-6">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center text-sm text-gray-600">
            <p>🔒 Enterprise Grade Security • SOC2 Compliant • © 2024 Zap ERP Systems</p>
        </div>
    </footer>

    <script>
        function togglePassword() {
            const passwordInput = document.getElementById('password');
            const toggleIcon = document.getElementById('toggleIcon');
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                toggleIcon.textContent = 'visibility_off';
            } else {
                passwordInput.type = 'password';
                toggleIcon.textContent = 'visibility';
            }
        }

        // Wait for Firebase to load
        function initializeAuth() {
            // Google Sign-In with Firebase
            document.getElementById('googleSignInBtn').addEventListener('click', async function() {
                try {
                    this.disabled = true;
                    this.innerHTML = '<span class="material-symbols-outlined animate-spin mr-2">progress_activity</span>Signing in with Google...';
                    
                    const result = await window.firebaseSignInWithPopup(window.firebaseAuth, window.googleProvider);
                    const user = result.user;
                    
                    // Get Firebase ID token
                    const idToken = await user.getIdToken();
                    
                    // Send to backend for verification and session creation
                    const response = await fetch('/api/v1/auth/firebase-login', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                        },
                        credentials: 'include', // Important: allows cookies to be set
                        body: JSON.stringify({
                            firebase_token: idToken,
                            email: user.email,
                            display_name: user.displayName,
                            photo_url: user.photoURL,
                            provider: 'google'
                        })
                    });
                    
                    const data = await response.json();
                    
                    if (response.ok && data.success) {
                        // Store user data, tokens, and org_slug in localStorage
                        localStorage.setItem('user', JSON.stringify(data.data.user));
                        localStorage.setItem('access_token', data.data.access_token);
                        localStorage.setItem('refresh_token', data.data.refresh_token);
                        localStorage.setItem('org_slug', data.data.organization.org_slug);
                        localStorage.setItem('org_data', JSON.stringify(data.data.organization));
                        localStorage.setItem('firebase_uid', user.uid);
                        
                        // Cookie is already set by server
                        
                        // Check if user is super admin (by email or special org_slug)
                        const isSuperAdmin = data.data.user.email === 'admin@zaperp.com' || 
                                           data.data.organization.org_slug === 'super-admin' ||
                                           data.data.user.is_super_admin === true;
                        
                        if (isSuperAdmin) {
                            // Super admin goes to control panel
                            window.location.href = '/control/dashboard';
                        } else {
                            // Regular tenant users go directly to their organization dashboard
                            const orgSlug = data.data.organization.org_slug;
                            window.location.href = `/org/${orgSlug}/dashboard`;
                        }
                    } else {
                        throw new Error(data.message || 'Authentication failed');
                    }
                } catch (error) {
                    console.error('Google Sign-In Error:', error);
                    document.getElementById('errorText').textContent = error.message || 'Failed to sign in with Google. Please try again.';
                    document.getElementById('errorMessage').classList.remove('hidden');
                    
                    this.disabled = false;
                    this.innerHTML = '<svg class="w-5 h-5" viewBox="0 0 24 24"><path fill="currentColor" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/><path fill="currentColor" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/><path fill="currentColor" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"/><path fill="currentColor" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/></svg><span class="font-medium">Continue with Google</span>';
                }
            });

            // Email/Password Sign-In with Backend API
            document.getElementById('loginForm').addEventListener('submit', async function(e) {
                e.preventDefault();
                
                const submitBtn = document.getElementById('submitBtn');
                const errorMessage = document.getElementById('errorMessage');
                
                // Clear previous messages
                errorMessage.classList.add('hidden');
                document.querySelectorAll('[id$="_error"]').forEach(el => el.classList.add('hidden'));
                
                // Disable submit button
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<span class="material-symbols-outlined animate-spin mr-2">progress_activity</span>Signing in...';
                
                const email = document.getElementById('email').value;
                const password = document.getElementById('password').value;
                const rememberMe = document.querySelector('input[name="remember"]').checked;
                
                try {
                    // Send to backend for authentication (org_slug will be auto-detected)
                    const response = await fetch('/api/v1/auth/login', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                        },
                        credentials: 'include', // Important: allows cookies to be set
                        body: JSON.stringify({
                            email: email,
                            password: password,
                            remember_me: rememberMe
                        })
                    });
                    
                    const data = await response.json();
                    
                    if (response.ok && data.success) {
                        // Store user data, tokens, and org_slug in localStorage
                        localStorage.setItem('user', JSON.stringify(data.data.user));
                        localStorage.setItem('access_token', data.data.access_token);
                        localStorage.setItem('refresh_token', data.data.refresh_token);
                        localStorage.setItem('org_slug', data.data.organization.org_slug);
                        localStorage.setItem('org_data', JSON.stringify(data.data.organization));
                        
                        // Cookie is already set by server
                        
                        // Check if user is super admin (by email or special org_slug)
                        const isSuperAdmin = data.data.user.email === 'admin@zaperp.com' || 
                                           data.data.organization.org_slug === 'super-admin' ||
                                           data.data.user.is_super_admin === true;
                        
                        if (isSuperAdmin) {
                            // Super admin goes to control panel
                            window.location.href = '/control/dashboard';
                        } else {
                            // Regular tenant users go directly to their organization dashboard
                            const orgSlug = data.data.organization.org_slug;
                            window.location.href = `/org/${orgSlug}/dashboard`;
                        }
                    } else {
                        throw new Error(data.message || 'Authentication failed');
                    }
                } catch (error) {
                    console.error('Sign-In Error:', error);
                    let errorMsg = 'Invalid credentials. Please try again.';
                    
                    if (error.message) {
                        errorMsg = error.message;
                    }
                    
                    document.getElementById('errorText').textContent = errorMsg;
                    errorMessage.classList.remove('hidden');
                } finally {
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = 'Sign In';
                }
            });
        }

        // Initialize when Firebase is loaded
        if (window.firebaseAuth) {
            initializeAuth();
        } else {
            window.addEventListener('firebase-loaded', initializeAuth);
        }
    </script>
</body>
</html>

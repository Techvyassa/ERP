<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Create Account - Zap ERP</title>
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
            <div class="flex items-center gap-4">
                <span class="text-sm text-gray-600">Already have an account?</span>
                <a href="/login" class="px-4 py-2 bg-white text-gray-900 border-2 border-gray-300 rounded-lg text-sm font-bold hover:bg-gray-50 transition-all">Sign In</a>
            </div>
        </div>
    </header>

    <!-- Registration Card -->
    <div class="flex-1 flex items-center justify-center p-4">
        <div class="w-full max-w-2xl">
            <div class="bg-white rounded-2xl shadow-lg border border-gray-200 p-8">
                <!-- Selected Plan Badge -->
                <div class="mb-6 p-4 bg-blue-50 rounded-lg border border-blue-200">
                    <div class="flex items-center justify-between">
                        <div>
                            <span class="text-sm text-gray-600">Selected Plan:</span>
                            <span class="ml-2 font-bold text-gray-900" id="selectedPlan">Professional</span>
                        </div>
                        <a href="/pricing" class="text-sm text-primary hover:text-primary/80 font-bold">Change Plan</a>
                    </div>
                </div>

                <!-- Title -->
                <h1 class="text-3xl font-bold text-gray-900 text-center mb-2">Create Your Account</h1>
                <p class="text-gray-600 text-center mb-8">Set up your organization and start your 14-day free trial</p>

            <!-- Google Sign Up -->
            <button type="button" id="googleSignUpBtn" class="w-full flex items-center justify-center space-x-3 px-6 py-3 bg-white border-2 border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition-colors mb-6">
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

            <!-- Registration Form -->
            <form id="registerForm" class="space-y-4">
                <div class="grid grid-cols-2 gap-4">
                    <!-- First Name -->
                    <div>
                        <label for="first_name" class="block text-sm font-medium text-gray-700 mb-2">First Name</label>
                    <input type="text" id="first_name" name="first_name" required
                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent"
                        placeholder="John">
                        <span class="text-xs text-red-600 hidden" id="first_name_error"></span>
                    </div>

                    <!-- Last Name -->
                    <div>
                        <label for="last_name" class="block text-sm font-medium text-gray-700 mb-2">Last Name</label>
                        <input type="text" id="last_name" name="last_name" required
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent"
                            placeholder="Doe">
                        <span class="text-xs text-red-600 hidden" id="last_name_error"></span>
                    </div>
                </div>

                <!-- Organization Name -->
                <div>
                    <label for="org_name" class="block text-sm font-medium text-gray-700 mb-2">Organization Name</label>
                    <input type="text" id="org_name" name="org_name" required
                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent"
                        placeholder="Acme Manufacturing Ltd.">
                    <span class="text-xs text-red-600 hidden" id="org_name_error"></span>
                </div>

                <!-- Email -->
                <div>
                    <label for="email" class="block text-sm font-medium text-gray-700 mb-2">Work Email</label>
                    <input type="email" id="email" name="email" required
                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent"
                        placeholder="john@company.com">
                    <span class="text-xs text-red-600 hidden" id="email_error"></span>
                </div>

                <!-- Password -->
                <div>
                    <label for="password" class="block text-sm font-medium text-gray-700 mb-2">Password</label>
                    <div class="relative">
                        <input type="password" id="password" name="password" required
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent pr-12"
                            placeholder="••••••••">
                        <button type="button" onclick="togglePassword()"
                            class="absolute right-3 top-1/2 transform -translate-y-1/2 text-gray-400 hover:text-gray-600">
                            <span class="material-symbols-outlined text-xl" id="toggleIcon">visibility</span>
                        </button>
                    </div>
                    <p class="text-xs text-gray-500 mt-1">Minimum 8 characters</p>
                    <span class="text-xs text-red-600 hidden" id="password_error"></span>
                </div>

                <!-- Terms & Conditions -->
                <div class="flex items-start">
                    <input type="checkbox" id="terms" name="terms" required
                        class="w-4 h-4 text-primary border-gray-300 rounded focus:ring-primary mt-1">
                    <label for="terms" class="ml-2 text-sm text-gray-700">
                        I agree to the <a href="#" class="text-primary hover:text-primary/80 font-bold">Terms of Service</a> and 
                        <a href="#" class="text-primary hover:text-primary/80 font-bold">Privacy Policy</a>
                    </label>
                </div>

                <!-- Error Message -->
                <div id="errorMessage" class="hidden bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg">
                    <div class="flex items-center">
                        <span class="material-symbols-outlined mr-2">error</span>
                        <span id="errorText"></span>
                    </div>
                </div>

                <!-- Submit Button -->
                <button type="submit" id="submitBtn"
                    class="w-full px-6 py-3 bg-primary text-white font-bold rounded-lg hover:bg-primary/90 transition-colors shadow-lg shadow-primary/20">
                    Create Account & Continue
                </button>
            </form>

            <!-- Sign In Link -->
            <div class="mt-6 text-center">
                <span class="text-sm text-gray-600">Already have an account? </span>
                <a href="/login" class="text-sm text-primary hover:text-primary/80 font-bold">Sign In</a>
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
        // Get selected plan from URL or localStorage
        const urlParams = new URLSearchParams(window.location.search);
        const planFromUrl = urlParams.get('plan');
        const planFromStorage = localStorage.getItem('selected_plan');
        const selectedPlan = planFromUrl || planFromStorage || 'professional';
        
        document.getElementById('selectedPlan').textContent = selectedPlan.charAt(0).toUpperCase() + selectedPlan.slice(1);

        // Auto-generate slug from organization name
        document.getElementById('org_name').addEventListener('input', function(e) {
            const slug = e.target.value
                .toLowerCase()
                .replace(/[^a-z0-9]+/g, '-')
                .replace(/^-+|-+$/g, '');
        });

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

        // Wait for Firebase to load before initializing
        function initializeRegistration() {
            // Google Sign-Up with Firebase
            document.getElementById('googleSignUpBtn').addEventListener('click', async function() {
                try {
                    // Validate organization name is filled
                    const orgName = document.getElementById('org_name').value.trim();
                    if (!orgName) {
                        document.getElementById('errorText').textContent = 'Please enter your organization name before continuing with Google.';
                        document.getElementById('errorMessage').classList.remove('hidden');
                        document.getElementById('org_name').focus();
                        return;
                    }
                    
                    this.disabled = true;
                    this.innerHTML = '<span class="material-symbols-outlined animate-spin mr-2">progress_activity</span>Signing up with Google...';
                    
                    const result = await window.firebaseSignInWithPopup(window.firebaseAuth, window.googleProvider);
                    const user = result.user;
                    
                    // Get Firebase ID token
                    const idToken = await user.getIdToken();
                    
                    // Extract name parts
                    const nameParts = (user.displayName || '').split(' ');
                    const firstName = nameParts[0] || '';
                    const lastName = nameParts.slice(1).join(' ') || '';
                    
                    // Generate org slug from org name
                    const orgSlug = orgName.toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/^-+|-+$/g, '');
                    
                    // Send to backend for registration
                    const response = await fetch('/api/v1/organizations/register', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                        },
                        body: JSON.stringify({
                            first_name: firstName,
                            last_name: lastName,
                            org_name: orgName,
                            org_slug: orgSlug,
                            primary_email: user.email,
                            firebase_uid: user.uid,
                            firebase_token: idToken,
                            provider: 'google',
                            photo_url: user.photoURL,
                            country_code: 'US',
                            selected_plan: selectedPlan
                        })
                    });
                    
                    const data = await response.json();
                    
                    if (response.ok && data.success) {
                        localStorage.setItem('org_data', JSON.stringify(data.data));
                        localStorage.setItem('firebase_uid', user.uid);
                        window.location.href = '/login?registered=true';
                    } else {
                        // Display validation errors if available
                        if (data.error && data.error.details) {
                            const errors = data.error.details;
                            let errorMsg = data.message + ':\n';
                            for (const [field, messages] of Object.entries(errors)) {
                                errorMsg += `\n• ${messages.join(', ')}`;
                            }
                            throw new Error(errorMsg);
                        }
                        throw new Error(data.message || 'Registration failed');
                    }
                } catch (error) {
                    console.error('Google Sign-Up Error:', error);
                    document.getElementById('errorText').textContent = error.message || 'Failed to sign up with Google. Please try again.';
                    document.getElementById('errorMessage').classList.remove('hidden');
                    
                    this.disabled = false;
                    this.innerHTML = '<svg class="w-5 h-5" viewBox="0 0 24 24"><path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/><path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/><path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"/><path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/></svg><span class="font-medium">Continue with Google</span>';
                }
            });

            // Email/Password Registration with Firebase
            document.getElementById('registerForm').addEventListener('submit', async function(e) {
                e.preventDefault();
                
                const submitBtn = document.getElementById('submitBtn');
                const errorMessage = document.getElementById('errorMessage');
                
                // Clear previous messages
                errorMessage.classList.add('hidden');
                document.querySelectorAll('[id$="_error"]').forEach(el => el.classList.add('hidden'));
                
                // Disable submit button
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<span class="material-symbols-outlined animate-spin mr-2">progress_activity</span>Creating your account...';
                
                const email = document.getElementById('email').value;
                const password = document.getElementById('password').value;
                
                try {
                    // Create user in Firebase
                    const userCredential = await window.firebaseCreateUserWithEmailAndPassword(window.firebaseAuth, email, password);
                    const user = userCredential.user;
                    
                    // Get Firebase ID token
                    const idToken = await user.getIdToken();
                    
                    // Send to backend for registration
                    const formData = {
                        first_name: document.getElementById('first_name').value,
                        last_name: document.getElementById('last_name').value,
                        org_name: document.getElementById('org_name').value,
                        org_slug: document.getElementById('org_name').value.toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/^-+|-+$/g, ''),
                        primary_email: email,
                        firebase_uid: user.uid,
                        firebase_token: idToken,
                        provider: 'email',
                        country_code: 'US',
                        selected_plan: selectedPlan
                    };
                    
                    const response = await fetch('/api/v1/organizations/register', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                        },
                        body: JSON.stringify(formData)
                    });
                    
                    const data = await response.json();
                    
                    if (response.ok && data.success) {
                        localStorage.setItem('org_data', JSON.stringify(data.data));
                        localStorage.setItem('firebase_uid', user.uid);
                        window.location.href = '/login?registered=true';
                    } else {
                        // If backend registration fails, delete Firebase user
                        await user.delete();
                        
                        // Display validation errors if available
                        if (data.error && data.error.details) {
                            const errors = data.error.details;
                            let errorMsg = data.message + ':\n';
                            for (const [field, messages] of Object.entries(errors)) {
                                errorMsg += `\n• ${messages.join(', ')}`;
                                // Highlight the field with error
                                const fieldElement = document.getElementById(field);
                                if (fieldElement) {
                                    fieldElement.classList.add('border-red-500');
                                    const errorSpan = document.getElementById(`${field}_error`);
                                    if (errorSpan) {
                                        errorSpan.textContent = messages.join(', ');
                                        errorSpan.classList.remove('hidden');
                                    }
                                }
                            }
                            throw new Error(errorMsg);
                        }
                        throw new Error(data.message || 'Registration failed');
                    }
                } catch (error) {
                    console.error('Registration Error:', error);
                    let errorMsg = 'Registration failed. Please try again.';
                    
                    if (error.code === 'auth/email-already-in-use') {
                        errorMsg = 'An account with this email already exists.';
                    } else if (error.code === 'auth/invalid-email') {
                        errorMsg = 'Invalid email address.';
                    } else if (error.code === 'auth/weak-password') {
                        errorMsg = 'Password should be at least 6 characters.';
                    } else if (error.message) {
                        errorMsg = error.message;
                    }
                    
                    document.getElementById('errorText').textContent = errorMsg;
                    errorMessage.classList.remove('hidden');
                } finally {
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = 'Create Account & Continue';
                }
            });
        }

        // Initialize when Firebase is loaded
        if (window.firebaseAuth) {
            initializeRegistration();
        } else {
            window.addEventListener('firebase-loaded', initializeRegistration);
        }
    </script>
</body>
</html>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Firebase Test - Zap ERP</title>
    <script src="https://cdn.tailwindcss.com"></script>
    
    <!-- Firebase Configuration -->
    @include('components.firebase-config')
</head>
<body class="bg-gray-100 p-8">
    <div class="max-w-2xl mx-auto bg-white rounded-lg shadow-lg p-8">
        <h1 class="text-3xl font-bold mb-6">Firebase Authentication Test</h1>
        
        <div class="space-y-4">
            <div class="p-4 bg-blue-50 rounded">
                <h2 class="font-semibold mb-2">Firebase Status:</h2>
                <div id="firebaseStatus" class="text-sm">Checking...</div>
            </div>

            <button id="testGoogleSignIn" class="w-full px-6 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                Test Google Sign-In
            </button>

            <div id="result" class="p-4 bg-gray-50 rounded hidden">
                <h3 class="font-semibold mb-2">Result:</h3>
                <pre id="resultText" class="text-xs overflow-auto"></pre>
            </div>

            <div id="error" class="p-4 bg-red-50 text-red-700 rounded hidden">
                <h3 class="font-semibold mb-2">Error:</h3>
                <pre id="errorText" class="text-xs overflow-auto"></pre>
            </div>
        </div>

        <div class="mt-6">
            <a href="/login" class="text-blue-600 hover:underline">← Back to Login</a>
        </div>
    </div>

    <script>
        function checkFirebaseStatus() {
            const statusDiv = document.getElementById('firebaseStatus');
            
            if (window.firebaseAuth) {
                statusDiv.innerHTML = `
                    <div class="text-green-600">✅ Firebase Initialized</div>
                    <div class="text-sm mt-2">
                        <strong>Config:</strong><br>
                        API Key: ${window.firebaseAuth.app.options.apiKey.substring(0, 20)}...<br>
                        Auth Domain: ${window.firebaseAuth.app.options.authDomain}<br>
                        Project ID: ${window.firebaseAuth.app.options.projectId}
                    </div>
                `;
            } else {
                statusDiv.innerHTML = '<div class="text-red-600">❌ Firebase Not Initialized</div>';
            }
        }

        function initTest() {
            checkFirebaseStatus();

            document.getElementById('testGoogleSignIn').addEventListener('click', async function() {
                const resultDiv = document.getElementById('result');
                const errorDiv = document.getElementById('error');
                const resultText = document.getElementById('resultText');
                const errorText = document.getElementById('errorText');

                resultDiv.classList.add('hidden');
                errorDiv.classList.add('hidden');

                this.disabled = true;
                this.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Testing...';

                try {
                    console.log('Starting Google Sign-In test...');
                    console.log('Firebase Auth:', window.firebaseAuth);
                    console.log('Google Provider:', window.googleProvider);
                    console.log('signInWithPopup function:', window.firebaseSignInWithPopup);

                    const result = await window.firebaseSignInWithPopup(window.firebaseAuth, window.googleProvider);
                    
                    console.log('Sign-in successful:', result);

                    resultText.textContent = JSON.stringify({
                        uid: result.user.uid,
                        email: result.user.email,
                        displayName: result.user.displayName,
                        photoURL: result.user.photoURL,
                        providerId: result.providerId
                    }, null, 2);
                    
                    resultDiv.classList.remove('hidden');

                    // Sign out after test
                    await window.firebaseSignOut(window.firebaseAuth);
                    console.log('Signed out successfully');

                } catch (error) {
                    console.error('Sign-in error:', error);
                    
                    errorText.textContent = JSON.stringify({
                        code: error.code,
                        message: error.message,
                        stack: error.stack
                    }, null, 2);
                    
                    errorDiv.classList.remove('hidden');
                } finally {
                    this.disabled = false;
                    this.innerHTML = 'Test Google Sign-In';
                }
            });
        }

        // Initialize when Firebase is loaded
        if (window.firebaseAuth) {
            initTest();
        } else {
            window.addEventListener('firebase-loaded', initTest);
        }

        // Also check status every second for first 5 seconds
        let checks = 0;
        const interval = setInterval(() => {
            checkFirebaseStatus();
            checks++;
            if (checks >= 5 || window.firebaseAuth) {
                clearInterval(interval);
            }
        }, 1000);
    </script>
</body>
</html>

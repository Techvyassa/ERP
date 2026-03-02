<script type="module">
    // Import Firebase SDKs
    import { initializeApp } from "https://www.gstatic.com/firebasejs/12.10.0/firebase-app.js";
    import { getAuth, signInWithPopup, GoogleAuthProvider, createUserWithEmailAndPassword, signInWithEmailAndPassword, signOut } from "https://www.gstatic.com/firebasejs/12.10.0/firebase-auth.js";
    import { getAnalytics } from "https://www.gstatic.com/firebasejs/12.10.0/firebase-analytics.js";

    // Firebase configuration from environment
    const firebaseConfig = {
        apiKey: "{{ env('FIREBASE_API_KEY') }}",
        authDomain: "{{ env('FIREBASE_AUTH_DOMAIN') }}",
        projectId: "{{ env('FIREBASE_PROJECT_ID') }}",
        storageBucket: "{{ env('FIREBASE_STORAGE_BUCKET') }}",
        messagingSenderId: "{{ env('FIREBASE_MESSAGING_SENDER_ID') }}",
        appId: "{{ env('FIREBASE_APP_ID') }}",
        measurementId: "{{ env('FIREBASE_MEASUREMENT_ID') }}"
    };

    console.log('Firebase Config:', firebaseConfig);

    try {
        // Initialize Firebase
        const app = initializeApp(firebaseConfig);
        const auth = getAuth(app);
        const analytics = getAnalytics(app);
        const googleProvider = new GoogleAuthProvider();
        
        // Add additional scopes for Google Sign-In
        googleProvider.addScope('profile');
        googleProvider.addScope('email');

        // Make Firebase auth available globally
        window.firebaseApp = app;
        window.firebaseAuth = auth;
        window.firebaseAnalytics = analytics;
        window.googleProvider = googleProvider;
        
        // Export auth functions to window
        window.firebaseSignInWithPopup = signInWithPopup;
        window.firebaseCreateUserWithEmailAndPassword = createUserWithEmailAndPassword;
        window.firebaseSignInWithEmailAndPassword = signInWithEmailAndPassword;
        window.firebaseSignOut = signOut;

        console.log('✅ Firebase initialized successfully');
        console.log('Auth:', auth);
        console.log('Google Provider:', googleProvider);
        
        // Dispatch event when Firebase is ready
        window.dispatchEvent(new Event('firebase-loaded'));
    } catch (error) {
        console.error('❌ Firebase initialization error:', error);
        alert('Firebase initialization failed. Please check console for details.');
    }
</script>

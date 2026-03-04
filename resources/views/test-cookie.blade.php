<!DOCTYPE html>
<html>
<head>
    <title>Cookie Test</title>
</head>
<body>
    <h1>Cookie Test Page</h1>
    
    <h2>Server-Side Cookies (PHP):</h2>
    <pre>{{ json_encode($_COOKIE, JSON_PRETTY_PRINT) }}</pre>
    
    <h2>Laravel Request Cookies:</h2>
    <pre>{{ json_encode(request()->cookies->all(), JSON_PRETTY_PRINT) }}</pre>
    
    <h2>Auth Token Cookie:</h2>
    <pre>{{ request()->cookie('auth_token') ?? 'NOT FOUND' }}</pre>
    
    <h2>Client-Side Cookies (JavaScript):</h2>
    <pre id="js-cookies"></pre>
    
    <h2>LocalStorage:</h2>
    <pre id="localstorage"></pre>
    
    <script>
        document.getElementById('js-cookies').textContent = document.cookie || 'No cookies';
        document.getElementById('localstorage').textContent = JSON.stringify({
            access_token: localStorage.getItem('access_token') ? 'EXISTS' : 'NOT FOUND',
            refresh_token: localStorage.getItem('refresh_token') ? 'EXISTS' : 'NOT FOUND',
            org_slug: localStorage.getItem('org_slug'),
            user: localStorage.getItem('user') ? 'EXISTS' : 'NOT FOUND'
        }, null, 2);
    </script>
</body>
</html>

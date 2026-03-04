<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Debug Profile Completion</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 p-8">
    <div class="max-w-4xl mx-auto">
        <h1 class="text-3xl font-bold mb-6">Profile Completion Debug</h1>
        
        <!-- Organization Data -->
        <div class="bg-white rounded-lg shadow p-6 mb-6">
            <h2 class="text-xl font-semibold mb-4">Organization Data (from Server)</h2>
            <pre class="bg-gray-100 p-4 rounded overflow-auto">{{ json_encode($organization, JSON_PRETTY_PRINT) }}</pre>
        </div>

        <!-- User Data from localStorage -->
        <div class="bg-white rounded-lg shadow p-6 mb-6">
            <h2 class="text-xl font-semibold mb-4">User Data (from localStorage)</h2>
            <pre id="userData" class="bg-gray-100 p-4 rounded overflow-auto"></pre>
        </div>

        <!-- Access Token -->
        <div class="bg-white rounded-lg shadow p-6 mb-6">
            <h2 class="text-xl font-semibold mb-4">Access Token</h2>
            <pre id="accessToken" class="bg-gray-100 p-4 rounded overflow-auto text-xs"></pre>
        </div>

        <!-- API Test Results -->
        <div class="bg-white rounded-lg shadow p-6 mb-6">
            <h2 class="text-xl font-semibold mb-4">API Test Results</h2>
            <button onclick="testProfileAPI()" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 mb-4">
                Test Profile Completion API
            </button>
            <button onclick="testMasterDataAPI()" class="px-4 py-2 bg-green-600 text-white rounded hover:bg-green-700 mb-4 ml-2">
                Test Master Data API
            </button>
            <pre id="apiResults" class="bg-gray-100 p-4 rounded overflow-auto"></pre>
        </div>

        <!-- Back Button -->
        <a href="{{ url(request()->get('tenant_type') === 'subdomain' ? '/dashboard' : '/org/' . $organization->org_slug . '/dashboard') }}" 
           class="inline-block px-6 py-2 bg-gray-600 text-white rounded hover:bg-gray-700">
            Back to Dashboard
        </a>
    </div>

    <script>
        // Display user data
        const user = JSON.parse(localStorage.getItem('user') || '{}');
        document.getElementById('userData').textContent = JSON.stringify(user, null, 2);

        // Display access token
        const token = localStorage.getItem('access_token') || 'No token found';
        document.getElementById('accessToken').textContent = token;

        async function testProfileAPI() {
            const results = document.getElementById('apiResults');
            results.textContent = 'Testing profile completion API...\n';
            
            try {
                const token = localStorage.getItem('access_token');
                const response = await fetch('/api/v1/profile-completion/status', {
                    headers: {
                        'Authorization': `Bearer ${token}`,
                        'Accept': 'application/json'
                    }
                });
                
                results.textContent += `Status: ${response.status} ${response.statusText}\n\n`;
                
                const data = await response.json();
                results.textContent += 'Response:\n' + JSON.stringify(data, null, 2);
            } catch (error) {
                results.textContent += 'Error: ' + error.message;
            }
        }

        async function testMasterDataAPI() {
            const results = document.getElementById('apiResults');
            results.textContent = 'Testing master data status API...\n';
            
            try {
                const token = localStorage.getItem('access_token');
                const response = await fetch('/api/v1/profile-completion/master-data-status', {
                    headers: {
                        'Authorization': `Bearer ${token}`,
                        'Accept': 'application/json'
                    }
                });
                
                results.textContent += `Status: ${response.status} ${response.statusText}\n\n`;
                
                const data = await response.json();
                results.textContent += 'Response:\n' + JSON.stringify(data, null, 2);
            } catch (error) {
                results.textContent += 'Error: ' + error.message;
            }
        }
    </script>
</body>
</html>

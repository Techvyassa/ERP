<!DOCTYPE html>
<html>
<head>
    <title>Tenant Diagnostic</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 p-8">
    <div class="max-w-4xl mx-auto">
        <h1 class="text-3xl font-bold mb-6">Tenant Diagnostic Page</h1>
        
        <div class="bg-white rounded-lg shadow p-6 mb-6">
            <h2 class="text-xl font-semibold mb-4">Current Request Info</h2>
            <table class="w-full">
                <tr class="border-b">
                    <td class="py-2 font-semibold">Host:</td>
                    <td class="py-2">{{ $host }}</td>
                </tr>
                <tr class="border-b">
                    <td class="py-2 font-semibold">Main Domain (config):</td>
                    <td class="py-2">{{ $mainDomain }}</td>
                </tr>
                <tr class="border-b">
                    <td class="py-2 font-semibold">Extracted Subdomain:</td>
                    <td class="py-2">{{ $subdomain ?? 'NONE' }}</td>
                </tr>
                <tr class="border-b">
                    <td class="py-2 font-semibold">Full URL:</td>
                    <td class="py-2">{{ request()->fullUrl() }}</td>
                </tr>
            </table>
        </div>
        
        <div class="bg-white rounded-lg shadow p-6 mb-6">
            <h2 class="text-xl font-semibold mb-4">Configuration</h2>
            <table class="w-full">
                <tr class="border-b">
                    <td class="py-2 font-semibold">APP_DOMAIN:</td>
                    <td class="py-2">{{ $config['app_domain'] }}</td>
                </tr>
                <tr class="border-b">
                    <td class="py-2 font-semibold">TENANT_MODE:</td>
                    <td class="py-2">{{ $config['tenant_mode'] }}</td>
                </tr>
                <tr class="border-b">
                    <td class="py-2 font-semibold">ALLOW_BOTH:</td>
                    <td class="py-2">{{ $config['allow_both'] ? 'Yes' : 'No' }}</td>
                </tr>
            </table>
        </div>
        
        <div class="bg-white rounded-lg shadow p-6">
            <h2 class="text-xl font-semibold mb-4">Organizations in Database</h2>
            @if($organizations->count() > 0)
                <table class="w-full">
                    <thead>
                        <tr class="border-b-2">
                            <th class="text-left py-2">ID</th>
                            <th class="text-left py-2">Slug</th>
                            <th class="text-left py-2">Name</th>
                            <th class="text-left py-2">Status</th>
                            <th class="text-left py-2">Test URLs</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($organizations as $org)
                        <tr class="border-b {{ $subdomain === $org->org_slug ? 'bg-green-50' : '' }}">
                            <td class="py-2">{{ $org->org_id }}</td>
                            <td class="py-2 font-mono">{{ $org->org_slug }}</td>
                            <td class="py-2">{{ $org->org_name }}</td>
                            <td class="py-2">
                                <span class="px-2 py-1 text-xs rounded {{ $org->registration_status === 'ACTIVE' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800' }}">
                                    {{ $org->registration_status }}
                                </span>
                            </td>
                            <td class="py-2">
                                <a href="http://{{ $org->org_slug }}.localhost/dashboard" class="text-blue-600 hover:underline text-sm mr-2" target="_blank">
                                    Subdomain
                                </a>
                                <a href="/org/{{ $org->org_slug }}/dashboard" class="text-blue-600 hover:underline text-sm" target="_blank">
                                    Path
                                </a>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            @else
                <p class="text-gray-500">No organizations found in database</p>
            @endif
        </div>
        
        <div class="mt-6 bg-blue-50 border border-blue-200 rounded-lg p-4">
            <h3 class="font-semibold mb-2">Instructions:</h3>
            <ol class="list-decimal list-inside space-y-1 text-sm">
                <li>Make sure you have added the subdomain to your hosts file</li>
                <li>Example: <code class="bg-gray-200 px-1">127.0.0.1 vishu.localhost</code></li>
                <li>Restart your browser after editing hosts file</li>
                <li>Visit: <code class="bg-gray-200 px-1">http://vishu.localhost/dashboard</code></li>
            </ol>
        </div>
        
        <div class="mt-4">
            <a href="/login" class="text-blue-600 hover:underline">← Back to Login</a>
        </div>
    </div>
</body>
</html>

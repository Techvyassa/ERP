@extends('tenant.layouts.app')

@section('title', 'Dashboard')
@section('page-title', 'Dashboard')

@section('content')
    <div x-data="{ user: {} }" x-init="user = JSON.parse(localStorage.getItem('user') || '{}')">
    <!-- Welcome Banner -->
    <div class="bg-gradient-to-r from-blue-600 to-purple-600 rounded-xl p-8 mb-8 text-white">
        <h1 class="text-3xl font-bold mb-2">
            <span x-text="user.first_name ? 'Welcome back, ' + user.first_name + '!' : 'Welcome to ' + '{{ $organization->org_name }}'"></span> 👋
        </h1>
        <p class="text-blue-100 mb-4">
            You're accessing via 
            @if($tenantType === 'subdomain')
                <strong>subdomain</strong> ({{ $organization->org_slug }}.{{ config('app.domain') }})
            @else
                <strong>path-based URL</strong> (/org/{{ $organization->org_slug }})
            @endif
        </p>
        <div class="flex space-x-4">
            <button class="px-6 py-2 bg-white text-blue-600 font-medium rounded-lg hover:bg-gray-100 transition-colors">
                <i class="fas fa-play mr-2"></i>Quick Tour
            </button>
            @if($tenantType === 'subdomain')
                <a href="{{ url('/org/' . $organization->org_slug . '/dashboard') }}" class="px-6 py-2 bg-blue-700 text-white font-medium rounded-lg hover:bg-blue-800 transition-colors">
                    <i class="fas fa-link mr-2"></i>Switch to Path-based URL
                </a>
            @else
                <a href="{{ 'https://' . $organization->org_slug . '.' . config('app.domain') . '/dashboard' }}" class="px-6 py-2 bg-blue-700 text-white font-medium rounded-lg hover:bg-blue-800 transition-colors">
                    <i class="fas fa-globe mr-2"></i>Switch to Subdomain
                </a>
            @endif
        </div>
    </div>

        <!-- Stats Grid -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
            <div class="bg-white rounded-xl shadow p-6">
                <div class="flex items-center justify-between mb-4">
                    <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center">
                        <i class="fas fa-users text-blue-600 text-xl"></i>
                    </div>
                    <span class="text-green-600 text-sm font-semibold">+12%</span>
                </div>
                <h3 class="text-2xl font-bold text-gray-900 mb-1">0</h3>
                <p class="text-gray-600 text-sm">Total Users</p>
            </div>

            <div class="bg-white rounded-xl shadow p-6">
                <div class="flex items-center justify-between mb-4">
                    <div class="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center">
                        <i class="fas fa-building text-green-600 text-xl"></i>
                    </div>
                    <span class="text-green-600 text-sm font-semibold">+8%</span>
                </div>
                <h3 class="text-2xl font-bold text-gray-900 mb-1">0</h3>
                <p class="text-gray-600 text-sm">Departments</p>
            </div>

            <div class="bg-white rounded-xl shadow p-6">
                <div class="flex items-center justify-between mb-4">
                    <div class="w-12 h-12 bg-yellow-100 rounded-lg flex items-center justify-center">
                        <i class="fas fa-user-shield text-yellow-600 text-xl"></i>
                    </div>
                    <span class="text-blue-600 text-sm font-semibold">Active</span>
                </div>
                <h3 class="text-2xl font-bold text-gray-900 mb-1">0</h3>
                <p class="text-gray-600 text-sm">Roles</p>
            </div>

            <div class="bg-white rounded-xl shadow p-6">
                <div class="flex items-center justify-between mb-4">
                    <div class="w-12 h-12 bg-purple-100 rounded-lg flex items-center justify-center">
                        <i class="fas fa-chart-line text-purple-600 text-xl"></i>
                    </div>
                    <span class="text-green-600 text-sm font-semibold">+15%</span>
                </div>
                <h3 class="text-2xl font-bold text-gray-900 mb-1">$0</h3>
                <p class="text-gray-600 text-sm">Revenue</p>
            </div>
        </div>

        <!-- Quick Actions & Recent Activity -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
            <!-- Quick Actions -->
            <div class="bg-white rounded-xl shadow p-6">
                <h2 class="text-lg font-semibold text-gray-900 mb-4">Quick Actions</h2>
                <div class="grid grid-cols-2 gap-4">
                    <button class="flex flex-col items-center justify-center p-6 border-2 border-gray-200 rounded-lg hover:border-blue-600 hover:bg-blue-50 transition-colors">
                        <i class="fas fa-user-plus text-3xl text-blue-600 mb-2"></i>
                        <span class="text-sm font-medium text-gray-900">Add User</span>
                    </button>
                    <button class="flex flex-col items-center justify-center p-6 border-2 border-gray-200 rounded-lg hover:border-green-600 hover:bg-green-50 transition-colors">
                        <i class="fas fa-building text-3xl text-green-600 mb-2"></i>
                        <span class="text-sm font-medium text-gray-900">Add Department</span>
                    </button>
                    <button class="flex flex-col items-center justify-center p-6 border-2 border-gray-200 rounded-lg hover:border-yellow-600 hover:bg-yellow-50 transition-colors">
                        <i class="fas fa-user-shield text-3xl text-yellow-600 mb-2"></i>
                        <span class="text-sm font-medium text-gray-900">Manage Roles</span>
                    </button>
                    <button class="flex flex-col items-center justify-center p-6 border-2 border-gray-200 rounded-lg hover:border-purple-600 hover:bg-purple-50 transition-colors">
                        <i class="fas fa-file-alt text-3xl text-purple-600 mb-2"></i>
                        <span class="text-sm font-medium text-gray-900">Generate Report</span>
                    </button>
                </div>
            </div>

            <!-- Recent Activity -->
            <div class="bg-white rounded-xl shadow p-6">
                <h2 class="text-lg font-semibold text-gray-900 mb-4">Recent Activity</h2>
                <div class="space-y-4">
                    <div class="flex items-start space-x-3 p-3 bg-gray-50 rounded-lg">
                        <div class="w-8 h-8 bg-green-100 rounded-full flex items-center justify-center flex-shrink-0">
                            <i class="fas fa-check text-green-600 text-sm"></i>
                        </div>
                        <div class="flex-1">
                            <p class="text-sm font-medium text-gray-900">Organization setup completed</p>
                            <p class="text-xs text-gray-600">Just now</p>
                        </div>
                    </div>
                    <div class="text-center py-8 text-gray-500">
                        <i class="fas fa-inbox text-4xl mb-2"></i>
                        <p class="text-sm">No recent activity yet</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

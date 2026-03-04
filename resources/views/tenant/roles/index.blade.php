@extends('tenant.layouts.app')

@section('title', 'Roles')
@section('page-title', 'Role Management')

@section('content')
    <div class="bg-white rounded-xl shadow p-6">
        <div class="flex items-center justify-between mb-6">
            <div>
                <h2 class="text-2xl font-bold text-gray-900">Roles</h2>
                <p class="text-gray-600 mt-1">Manage user roles and permissions</p>
            </div>
            <button class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                <i class="fas fa-plus mr-2"></i>Add Role
            </button>
        </div>

        <div class="text-center py-12">
            <i class="fas fa-user-shield text-6xl text-gray-300 mb-4"></i>
            <h3 class="text-xl font-semibold text-gray-700 mb-2">Role Management Coming Soon</h3>
            <p class="text-gray-600">This feature is under development.</p>
        </div>
    </div>
@endsection

@extends('hr.layouts.app')

@section('title', 'Create Department User')
@section('header', 'Create Department User')
@section('subheader', 'Assign role-based access to a department account.')

@section('content')
    <div class="max-w-3xl">

        @if($errors->any())
        <div class="mb-6 p-4 bg-red-900/40 border border-red-700 rounded-lg">
            <ul class="list-disc list-inside text-red-300 text-sm">
                @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
        @endif

        <form method="POST" action="{{ route('hr.users.store') }}" class="bg-gray-800 border border-gray-700 rounded-lg p-6 space-y-4">
            @csrf

            <div>
                <label class="block text-sm text-gray-300 mb-1">Name</label>
                <input name="name" value="{{ old('name') }}" required class="w-full px-3 py-2 rounded-lg bg-gray-900 border border-gray-600 text-white focus:outline-none focus:ring-2 focus:ring-orange-500">
            </div>

            <div>
                <label class="block text-sm text-gray-300 mb-1">Email</label>
                <input type="email" name="email" value="{{ old('email') }}" required class="w-full px-3 py-2 rounded-lg bg-gray-900 border border-gray-600 text-white focus:outline-none focus:ring-2 focus:ring-orange-500">
            </div>

            <div>
                <label class="block text-sm text-gray-300 mb-1">Department Role</label>
                <select name="role" required class="w-full px-3 py-2 rounded-lg bg-gray-900 border border-gray-600 text-white focus:outline-none focus:ring-2 focus:ring-orange-500">
                    <option value="">Select role</option>
                    @foreach($roles as $role)
                    <option value="{{ $role }}" {{ old('role') === $role ? 'selected' : '' }}>{{ ucfirst(str_replace('_', ' ', $role)) }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-sm text-gray-300 mb-1">Status</label>
                <select name="status" class="w-full px-3 py-2 rounded-lg bg-gray-900 border border-gray-600 text-white focus:outline-none focus:ring-2 focus:ring-orange-500">
                    <option value="active" {{ old('status') === 'active' ? 'selected' : '' }}>Active</option>
                    <option value="inactive" {{ old('status') === 'inactive' ? 'selected' : '' }}>Inactive</option>
                    <option value="suspended" {{ old('status') === 'suspended' ? 'selected' : '' }}>Suspended</option>
                </select>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm text-gray-300 mb-1">Password</label>
                    <input type="password" name="password" required class="w-full px-3 py-2 rounded-lg bg-gray-900 border border-gray-600 text-white focus:outline-none focus:ring-2 focus:ring-orange-500">
                </div>
                <div>
                    <label class="block text-sm text-gray-300 mb-1">Confirm Password</label>
                    <input type="password" name="password_confirmation" required class="w-full px-3 py-2 rounded-lg bg-gray-900 border border-gray-600 text-white focus:outline-none focus:ring-2 focus:ring-orange-500">
                </div>
            </div>

            <div class="flex gap-3 pt-2">
                <button type="submit" class="px-5 py-2 bg-orange-600 hover:bg-orange-700 text-white rounded-lg font-semibold">Create User</button>
                <a href="{{ route('hr.users.index') }}" class="px-5 py-2 bg-gray-700 hover:bg-gray-600 text-white rounded-lg">Cancel</a>
            </div>
        </form>
    </div>
@endsection

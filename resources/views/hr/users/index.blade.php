@extends('hr.layouts.app')

@section('title', 'Department Users')
@section('header', 'Department Users')
@section('subheader', 'Manage role-based access for workforce departments.')

@section('header-actions')
<a href="{{ route('hr.users.create') }}" class="rounded-lg bg-orange-600 px-4 py-2 font-semibold text-white hover:bg-orange-700">+ Add User</a>
@endsection

@section('content')
    <div class="max-w-7xl">

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
            <a href="{{ route('employees.index') }}" class="rounded-lg border border-gray-700 bg-gray-800 p-4 hover:border-orange-500 transition">
                <p class="text-sm font-semibold text-orange-400">Employee Hiring</p>
                <p class="mt-1 text-sm text-gray-400">Create and manage employee accounts</p>
            </a>
            <a href="{{ route('payroll.index') }}" class="rounded-lg border border-gray-700 bg-gray-800 p-4 hover:border-orange-500 transition">
                <p class="text-sm font-semibold text-orange-400">Payroll Preparation</p>
                <p class="mt-1 text-sm text-gray-400">Prepare payroll records for owner approval</p>
            </a>
            <a href="{{ route('attendance.index') }}" class="rounded-lg border border-gray-700 bg-gray-800 p-4 hover:border-orange-500 transition">
                <p class="text-sm font-semibold text-orange-400">Attendance</p>
                <p class="mt-1 text-sm text-gray-400">Review attendance before payroll processing</p>
            </a>
        </div>

        @if(session('success'))
        <div class="mb-6 p-4 bg-green-900/40 border border-green-700 rounded-lg text-green-300">{{ session('success') }}</div>
        @endif

        <div class="bg-gray-800 border border-gray-700 rounded-lg overflow-hidden">
            @if($users->count() > 0)
            <table class="w-full text-sm">
                <thead class="bg-gray-700 border-b border-gray-600">
                    <tr>
                        <th class="px-6 py-3 text-left">Name</th>
                        <th class="px-6 py-3 text-left">Email</th>
                        <th class="px-6 py-3 text-left">Department</th>
                        <th class="px-6 py-3 text-left">Status</th>
                        <th class="px-6 py-3 text-left">Created</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-700">
                    @foreach($users as $user)
                    <tr class="hover:bg-gray-700/60">
                        <td class="px-6 py-4 font-semibold text-white">{{ $user->name }}</td>
                        <td class="px-6 py-4 text-gray-300">{{ $user->email }}</td>
                        <td class="px-6 py-4">
                            <span class="px-2 py-1 rounded text-xs bg-blue-900 text-blue-300">{{ ucfirst(str_replace('_', ' ', $user->role)) }}</span>
                        </td>
                        <td class="px-6 py-4">
                            <span class="px-2 py-1 rounded text-xs {{ $user->status === 'active' ? 'bg-green-900 text-green-300' : 'bg-gray-700 text-gray-300' }}">{{ ucfirst($user->status ?? 'active') }}</span>
                        </td>
                        <td class="px-6 py-4 text-gray-400">{{ $user->created_at?->format('M d, Y') }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
            <div class="px-6 py-4 border-t border-gray-700">{{ $users->links() }}</div>
            @else
            <div class="px-6 py-10 text-center text-gray-400">No department users yet.</div>
            @endif
        </div>
    </div>
@endsection

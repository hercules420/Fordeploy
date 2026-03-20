<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\FarmOwner;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class FarmOwnerAuthController extends Controller
{
    public function show_login()
    {
        if (Auth::check() && Auth::user()->role === 'farm_owner') {
            $farmOwner = Auth::user()->farmOwner;

            if ($farmOwner && $farmOwner->permit_status !== 'approved') {
                return redirect()->route('farmowner.pending');
            }

            return redirect()->route('farmowner.dashboard');
        }
        return view('farmowner.login');
    }

    public function login(Request $request)
    {
        $validated = $request->validate([
            'email' => 'required|email:rfc,dns',
            'password' => 'required|string|min:8',
        ]);

        $existingUser = User::where('email', $validated['email'])->first();

        if ($existingUser && $existingUser->role !== 'farm_owner') {
            return back()->withErrors(['email' => 'This account is not a farm owner account. Use the main login page.'])->withInput();
        }

        $user = User::where('email', $validated['email'])->where('role', 'farm_owner')->first();

        if (!$user || !Hash::check($validated['password'], $user->password)) {
            return back()->withErrors(['email' => 'Invalid credentials'])->withInput();
        }

        Auth::login($user, $request->boolean('remember'));
        $request->session()->regenerate();

        Log::info('Farm owner logged in', ['user_id' => $user->id]);

        $farmOwner = $user->farmOwner;

        if ($farmOwner && $farmOwner->permit_status !== 'approved') {
            return redirect()->route('farmowner.pending')
                ->with('success', 'Your registration is under review by Super Admin.');
        }

        return redirect()->route('farmowner.dashboard');
    }

    public function show_register()
    {
        if (Auth::check() && Auth::user()->role === 'farm_owner') {
            return redirect()->route('farmowner.dashboard');
        }
        return view('farmowner.register');
    }

    public function register(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email:rfc,dns|unique:users',
            'password' => 'required|string|min:8|confirmed',
            'phone' => 'nullable|string|max:20',
            'farm_name' => 'required|string|max:255|unique:farm_owners',
            'farm_address' => 'required|string|max:500',
            'city' => 'required|string|max:255',
            'province' => 'required|string|max:255',
            'postal_code' => 'nullable|string|max:10',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'business_registration_number' => 'required|string|unique:farm_owners',
            'valid_id' => 'required|image|mimes:jpg,jpeg,png,webp|max:5120',
        ]);

        // Upload valid ID
        $valid_id_path = $request->file('valid_id')->store('valid_ids', 'public');

        // Create user
        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'phone' => $validated['phone'],
            'role' => 'farm_owner',
            'status' => 'active',
            'email_verified_at' => now(),
        ]);

        // Create farm owner profile
        FarmOwner::create([
            'user_id' => $user->id,
            'farm_name' => $validated['farm_name'],
            'farm_address' => $validated['farm_address'],
            'city' => $validated['city'],
            'province' => $validated['province'],
            'postal_code' => $validated['postal_code'],
            'latitude' => $validated['latitude'],
            'longitude' => $validated['longitude'],
            'business_registration_number' => $validated['business_registration_number'],
            'valid_id_path' => $valid_id_path,
            'permit_status' => 'pending',
        ]);

        Log::info('New farm owner registered', ['email' => $user->email, 'farm_name' => $validated['farm_name']]);

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('farmowner.pending')->with('success', 'Farm registered successfully. Awaiting admin verification.');
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('farmowner.login')->with('success', 'Logged out successfully');
    }
}

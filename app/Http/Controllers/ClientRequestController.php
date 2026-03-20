<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests\ClientRegistrationRequest;
use App\Models\ClientRequest;
use App\Models\FarmOwner;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ClientRequestController extends Controller
{
    /**
     * Store the farm owner's registration request.
     */
    public function store(ClientRegistrationRequest $request)
    {
        $uploadedPaths = [];

        try {
            $validated = $request->validated();
            $createdUserId = null;

            DB::transaction(function () use ($request, $validated, &$uploadedPaths, &$createdUserId): void {
                // Handle file uploads first so both request and farm_owner records can reference paths.
                $idPath = $request->file('valid_id')->store('uploads/ids', 'public');
                $permitPath = $request->file('business_permit')->store('uploads/permits', 'public');
                $uploadedPaths = [$idPath, $permitPath];

                ClientRequest::create([
                    'owner_name'           => $validated['owner_name'],
                    'farm_name'            => $validated['farm_name'],
                    'email'                => $validated['email'],
                    'farm_location'        => $validated['farm_location'],
                    'valid_id_path'        => $idPath,
                    'business_permit_path' => $permitPath,
                    'password'             => Hash::make($validated['password']),
                    'status'               => 'pending',
                ]);

                $user = User::create([
                    'name' => $validated['owner_name'],
                    'email' => $validated['email'],
                    'password' => Hash::make($validated['password']),
                    'role' => 'farm_owner',
                    'status' => 'active',
                ]);
                $createdUserId = $user->id;

                FarmOwner::create([
                    'user_id' => $user->id,
                    'farm_name' => $validated['farm_name'],
                    'farm_address' => $validated['farm_location'],
                    'city' => 'Pending',
                    'province' => 'Pending',
                    'business_registration_number' => 'REQ-' . Str::upper(Str::random(10)),
                    'valid_id_path' => $idPath,
                    'permit_status' => 'pending',
                    'subscription_status' => 'inactive',
                ]);

                Mail::raw(
                    "Your farm owner registration has been received and is pending Super Admin review.",
                    function ($message) use ($validated): void {
                        $message->to($validated['email'], $validated['owner_name'])
                            ->subject('Farm Owner Registration Received');
                    }
                );
            });

            if ($createdUserId) {
                Auth::loginUsingId($createdUserId);
                $request->session()->regenerate();
            }

            Log::info('Client registration request created', [
                'farm_name' => $validated['farm_name'],
                'email' => $validated['email'],
            ]);

            return redirect()
                ->route('farmowner.dashboard')
                ->with('success', 'Your farm application has been submitted. You can now explore the dashboard while waiting for Super Admin approval.');
        } catch (\Exception $e) {
            foreach ($uploadedPaths as $path) {
                if (Storage::disk('public')->exists($path)) {
                    Storage::disk('public')->delete($path);
                }
            }

            Log::error('Client registration failed', ['error' => $e->getMessage()]);
            return back()->withErrors([
                'error' => 'Registration could not be completed. Please use a real email address that can receive messages.',
            ]);
        }
    }
}
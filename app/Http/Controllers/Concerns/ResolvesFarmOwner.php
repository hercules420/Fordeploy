<?php

namespace App\Http\Controllers\Concerns;

use App\Models\Employee;
use App\Models\FarmOwner;
use Illuminate\Support\Facades\Auth;

trait ResolvesFarmOwner
{
    /**
     * Resolve the FarmOwner model regardless of whether the logged-in user
     * is a farm owner themselves or a department employee working under one.
     */
    protected function getFarmOwner(): FarmOwner
    {
        $user = Auth::user();

        // If the user IS a farm owner, resolve directly
        if ($user->isFarmOwner()) {
            return FarmOwner::where('user_id', $user->id)->firstOrFail();
        }

        // If the user is a department employee, find their employer's farm owner
        $employee = Employee::where('user_id', $user->id)->first();

        if ($employee && $employee->farm_owner_id) {
            return FarmOwner::findOrFail($employee->farm_owner_id);
        }

        abort(403, 'No farm owner associated with your account.');
    }
}

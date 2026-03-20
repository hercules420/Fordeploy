<?php

namespace App\Services;

use App\Models\ConsumerVerificationCode;
use App\Models\User;
use Illuminate\Support\Facades\Mail;

class ConsumerVerificationService
{
    public function issueCode(User $user): void
    {
        $code = (string) random_int(100000, 999999);

        ConsumerVerificationCode::updateOrCreate(
            ['user_id' => $user->id],
            [
                'code' => $code,
                'expires_at' => now()->addMinutes(10),
                'attempts' => 0,
            ]
        );

        Mail::raw(
            "Your Poultry Consumer verification code is: {$code}. This code expires in 10 minutes.",
            function ($message) use ($user): void {
                $message->to($user->email, $user->name)
                    ->subject('Your Consumer Verification Code');
            }
        );
    }
}

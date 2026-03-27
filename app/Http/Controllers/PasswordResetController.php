<?php

namespace App\Http\Controllers;

use App\Contracts\DatabaseConnectionRouter;
use App\Models\Control\Organization;
use App\Models\Tenant\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class PasswordResetController extends Controller
{
    public function __construct(
        private DatabaseConnectionRouter $connectionRouter
    ) {}

    /**
     * Send password reset link
     * POST /api/v1/auth/forgot-password
     */
    public function forgotPassword(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'error'   => ['code' => 'VALIDATION_ERROR', 'details' => $validator->errors()],
            ], 422);
        }

        $email = $request->input('email');

        // Always return success to prevent email enumeration
        try {
            $this->connectionRouter->switchToControl();

            $organizations = Organization::whereIn('registration_status', ['ACTIVE'])->get();

            $user         = null;
            $organization = null;

            foreach ($organizations as $org) {
                try {
                    $this->connectionRouter->switchToTenant($org->tenant_db_name);
                    $found = User::where('email', $email)->where('is_active', true)->first();
                    if ($found) {
                        $user         = $found;
                        $organization = $org;
                        break;
                    }
                } catch (\Exception $e) {
                    continue;
                }
            }

            if ($user && $organization) {
                $token = bin2hex(random_bytes(32));

                $user->password_reset_token      = hash('sha256', $token);
                $user->password_reset_expires_at = now()->addHour();
                $user->save();

                $resetUrl = rtrim(config('app.url'), '/') . '/reset-password?token=' . $token . '&email=' . urlencode($email);

                Mail::send('emails.password-reset', [
                    'firstName' => $user->first_name,
                    'resetUrl'  => $resetUrl,
                    'expiresIn' => '1 hour',
                ], function ($message) use ($email, $user) {
                    $message->to($email, $user->first_name . ' ' . $user->last_name)
                            ->subject('Reset Your Password - Zap ERP');
                });
            }
        } catch (\Exception $e) {
            \Log::error('Password reset error', ['error' => $e->getMessage()]);
        }

        return response()->json([
            'success' => true,
            'message' => 'If an account with that email exists, a password reset link has been sent.',
        ]);
    }

    /**
     * Reset password using token
     * POST /api/v1/auth/reset-password
     */
    public function resetPassword(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'email'                 => 'required|email',
            'token'                 => 'required|string',
            'password'              => 'required|string|min:8|confirmed',
            'password_confirmation' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'error'   => ['code' => 'VALIDATION_ERROR', 'details' => $validator->errors()],
            ], 422);
        }

        $email    = $request->input('email');
        $token    = $request->input('token');
        $password = $request->input('password');

        try {
            $this->connectionRouter->switchToControl();

            $organizations = Organization::whereIn('registration_status', ['ACTIVE'])->get();

            $user = null;

            foreach ($organizations as $org) {
                try {
                    $this->connectionRouter->switchToTenant($org->tenant_db_name);
                    $found = User::where('email', $email)
                        ->where('is_active', true)
                        ->whereNotNull('password_reset_token')
                        ->first();

                    if ($found) {
                        $user = $found;
                        break;
                    }
                } catch (\Exception $e) {
                    continue;
                }
            }

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid or expired reset token.',
                    'error'   => ['code' => 'INVALID_TOKEN', 'details' => []],
                ], 400);
            }

            // Validate token and expiry
            if (!hash_equals($user->password_reset_token, hash('sha256', $token))) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid or expired reset token.',
                    'error'   => ['code' => 'INVALID_TOKEN', 'details' => []],
                ], 400);
            }

            if (now()->isAfter($user->password_reset_expires_at)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Reset token has expired. Please request a new one.',
                    'error'   => ['code' => 'TOKEN_EXPIRED', 'details' => []],
                ], 400);
            }

            // Update password and clear reset token
            $user->password_hash             = $password;
            $user->password_reset_token      = null;
            $user->password_reset_expires_at = null;
            $user->save();

            return response()->json([
                'success' => true,
                'message' => 'Password has been reset successfully. You can now sign in.',
            ]);

        } catch (\Exception $e) {
            \Log::error('Password reset error', ['error' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'message' => 'An error occurred. Please try again.',
                'error'   => ['code' => 'SERVER_ERROR', 'details' => []],
            ], 500);
        }
    }
}

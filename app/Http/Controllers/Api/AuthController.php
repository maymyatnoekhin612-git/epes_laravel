<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Mail;
use App\Mail\VerificationEmail;
use App\Mail\PasswordResetEmail;
use Carbon\Carbon;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Create user but don't save to database yet
        $user = new User([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'verification_token' => Str::random(60),
            'verification_token_expires_at' => Carbon::now()->addHours(24),
        ]);

        // Save to get an ID
        $user->email_verified_at = null;
        $user->save();

        // Send verification email
        Mail::to($user->email)->send(new VerificationEmail($user));

        return response()->json([
            'message' => 'Registration successful! Please check your email to verify your account.',
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'is_verified' => false,
            ]
        ], 201);
    }

    public function verifyEmail(Request $request, $token)
    {
        $user = User::where('verification_token', $token)
                    ->where('verification_token_expires_at', '>', Carbon::now())
                    ->first();

        if (!$user) {
            return response()->json(['message' => 'Invalid or expired verification token.'], 400);
        }

        $user->email_verified_at = Carbon::now();
        $user->verification_token = null;
        $user->verification_token_expires_at = null;
        $user->save();

        return response()->json(['message' => 'Email verified successfully! You can now login.',
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
            ]
        ], 200);
    }

    public function resendVerification(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|exists:users,email',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $user = User::where('email', $request->email)
                    ->whereNull('email_verified_at')
                    ->first();

        if (!$user) {
            return response()->json([
                'message' => 'User not found or already verified.'
            ], 400);
        }

        // Generate new token if expired
        if ($user->verification_token_expires_at < Carbon::now()) {
            $user->verification_token = Str::random(60);
            $user->verification_token_expires_at = Carbon::now()->addHours(24);
            $user->save();
        }

        // Resend verification email
        Mail::to($user->email)->send(new VerificationEmail($user));

        return response()->json([
            'message' => 'Verification email resent successfully.'
        ], 200);
    }

    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|string|email',
            'password' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json(['message' => 'Invalid credentials'], 401);
        }

        if (is_null($user->email_verified_at)) {
            return response()->json([
                'message' => 'Please verify your email before logging in.',
                'needs_verification' => true,
                'email' => $user->email
            ], 403);
        }   

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'access_token' => $token,
            'token_type' => 'Bearer',
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
            ]
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();
        return response()->json(['message' => 'Logged out successfully']);
    }

    public function forgotPassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|exists:users,email',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $user = User::where('email', $request->email)->first();
        $user->reset_password_token = Str::random(60);
        $user->reset_password_expires_at = Carbon::now()->addHours(1);
        $user->save();

        Mail::to($user->email)->send(new PasswordResetEmail($user));

        return response()->json(['message' => 'Password reset link sent to your email.'], 200);
    }

    public function resetPassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'token' => 'required|string',
            'password' => 'required|string|min:8|confirmed',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $user = User::where('reset_password_token', $request->token)
                    ->where('reset_password_expires_at', '>', Carbon::now())
                    ->first();

        if (!$user) {
            return response()->json(['message' => 'Invalid or expired reset token.'], 400);
        }

        $user->password = Hash::make($request->password);
        $user->reset_password_token = null;
        $user->reset_password_expires_at = null;
        $user->save();

        // Invalidate all existing tokens (optional but good for security)
        $user->tokens()->delete();

        return response()->json(['message' => 'Password reset successfully!'], 200);
    }

    public function user(Request $request)
    {
        return response()->json($request->user());
    }
}
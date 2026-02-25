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
            'email' => 'required|string|email|max:255',
            'password' => 'required|string|min:8|confirmed',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $existingUser = User::where('email', $request->email)->first();
        
        if ($existingUser) {
            if ($existingUser->hasVerifiedEmail()) {
                return response()->json([
                    'errors' => ['email' => ['This email is already registered.']]
                ], 422);
            }
            // Check if the unverified account is expired (older than 24 hours)
            if ($existingUser->created_at->lt(Carbon::now()->subHours(24))) {
                // Delete expired unverified account
                $existingUser->delete();
            } else {
                // Account is still within 24 hours, resend verification code
                $verificationCode = $existingUser->generateVerificationCode();
                Mail::to($existingUser->email)->send(new VerificationEmail($existingUser, $verificationCode));
                
                return response()->json([
                    'message' => 'An unverified account already exists. A new verification code has been sent to your email.',
                    'email' => $existingUser->email,
                    'user_id' => $existingUser->id,
                ], 200);
            }
        }

        // Create new user
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'email_verified_at' => null,
            'role' => 'user',
        ]);

        $verificationCode = $user->generateVerificationCode();
        Mail::to($user->email)->send(new VerificationEmail($user, $verificationCode));
        
        return response()->json([
            'message' => 'Registration successful! Please check your email for the verification code.',
            'email' => $user->email,
            'user_id' => $user->id,
        ], 201);
    }

    public function verifyEmail(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'code' => 'required|string|size:6',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $user = User::where('email', $request->email)->first();

        if (!$user) {
            return response()->json(['message' => 'User not found.'], 404);
        }

        // Check if already verified
        if ($user->hasVerifiedEmail()) {
            return response()->json(['message' => 'Email already verified.'], 400);
        }

        // Validate OTP code
        if (!$user->isVerificationCodeValid($request->code)) {
            $user->incrementVerificationAttempts();
            
            $attemptsLeft = 3 - $user->email_verification_attempts;
            
            return response()->json([
                'message' => 'Invalid or expired verification code.',
                'attempts_left' => $attemptsLeft > 0 ? $attemptsLeft : 0,
                'blocked' => $user->email_verification_attempts >= 3
            ], 400);
        }

         // Mark email as verified
        $user->email_verified_at = Carbon::now();
        $user->clearVerificationCode();
        $user->save();

        return response()->json([
            'message' => 'Email verified successfully! You can now login.',
        ], 200);
    }

    public function resendVerificationCode(Request $request)
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

        if ($user->email_verification_attempts >= 3) {
            return response()->json([
                'message' => 'Too many verification attempts. Please try later.'
            ], 429);
        }

        $verificationCode = $user->generateVerificationCode();

        // Resend verification email
        Mail::to($user->email)->send(new VerificationEmail($user, $verificationCode));

        return response()->json([
            'message' => 'New verification code sent successfully.',
            'email' => $user->email,
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
        
        if (!$user->hasVerifiedEmail()) {
            return response()->json([
                'message' => 'Please verify your email first.'
            ], 400);
        }

        $resetCode = $user->generatePasswordResetCode();

        // Send password reset email with code
        Mail::to($user->email)->send(new PasswordResetEmail($user, $resetCode));

        return response()->json([
            'message' => 'Password reset code sent to your email.',
            'email' => $user->email,
        ], 200);
    }

    public function verifyPasswordResetCode(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|exists:users,email',
            'code' => 'required|string|size:6',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $user = User::where('email', $request->email)->first();

        if (!$user->isPasswordResetCodeValid($request->code)) {
            $user->incrementPasswordResetAttempts();
            
            $attemptsLeft = 3 - $user->password_reset_attempts;
            
            return response()->json([
                'message' => 'Invalid or expired reset code.',
                'attempts_left' => $attemptsLeft > 0 ? $attemptsLeft : 0,
                'blocked' => $user->password_reset_attempts >= 3
            ], 400);
        }
        return response()->json([
            'message' => 'Reset code verified successfully.',
            'verified' => true,
        ], 200);
    }

    public function resetPassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|exists:users,email',
            'code' => 'required|string|size:6',
            'password' => 'required|string|min:8|confirmed',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $user = User::where('email', $request->email)->first();

        if (!$user->isPasswordResetCodeValid($request->code)) {
            return response()->json(['message' => 'Invalid or expired reset code.'], 400);
        }

        // Update password
        $user->password = Hash::make($request->password);
        $user->clearPasswordResetCode();
        
        // Invalidate all existing tokens
        $user->tokens()->delete();
        
        $user->save();

         return response()->json([
            'message' => 'Password reset successfully! You can now login with your new password.',
        ], 200);
    }

    public function user(Request $request)
    {
        return response()->json($request->user());
    }
}
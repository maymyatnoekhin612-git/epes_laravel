<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\TestController;
use App\Http\Controllers\Api\AdminController;

// Public routes
Route::post('/auth/register', [AuthController::class, 'register']);
Route::post('/auth/login', [AuthController::class, 'login']);
Route::post('/auth/forgot-password', [AuthController::class, 'forgotPassword']);
Route::post('/auth/reset-password', [AuthController::class, 'resetPassword']);
Route::get('/auth/verify/{token}', [AuthController::class, 'verifyEmail']);
Route::post('/auth/resend-verification', [AuthController::class, 'resendVerification']);


// Tests - available to all
Route::get('/tests', [TestController::class, 'index']);
Route::get('/tests/{id}', [TestController::class, 'show']);

// Test taking (requires auth or guest session)
Route::post('/tests/{testId}/start', [TestController::class, 'startTest']);
Route::get('/attempts/{attemptId}/questions', [TestController::class, 'getTestQuestions']);
Route::post('/attempts/{attemptId}/answer', [TestController::class, 'submitAnswer']);
Route::post('/attempts/{attemptId}/submit', [TestController::class, 'submitTest']);
Route::get('/attempts/{attemptId}/results', [TestController::class, 'getResults']);
Route::get('/guest-results/{guestSessionId}', [TestController::class, 'getGuestResults']);

// Authenticated routes
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/auth/user', [AuthController::class, 'user']);
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    
    Route::get('/user/attempts', [TestController::class, 'getUserAttempts']);
    
});
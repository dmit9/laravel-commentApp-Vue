<?php

use App\Http\Controllers\Api\CaptchaController;
use App\Http\Controllers\Api\CommentController;
use App\Http\Controllers\Api\UserController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;


Route::get('/users', [UserController::class, 'index']);
Route::get('/users/{id}', [UserController::class, 'show']);

Route::get('/comments', [CommentController::class, 'index']);
Route::post('/comments', [CommentController::class, 'store']);
Route::get('/comments/{id}', [CommentController::class, 'show']);
Route::post('/comments/{id}/reply', [CommentController::class, 'reply']);
Route::get('/comments/{id}/replies', [CommentController::class, 'replies']);
Route::get('/captcha', [CaptchaController::class, 'generate']);


Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

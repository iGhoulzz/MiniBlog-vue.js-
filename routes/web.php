<?php

use App\Http\Controllers\LoginController;
use App\Http\Controllers\PostController;
use App\Http\Controllers\RegisterController;
use App\Http\Controllers\CommentController;
use Illuminate\Support\Facades\Route;

Route::get('/welcome', function () {
    return view('welcome');
});

Route::get('/register', [RegisterController::class, 'create']);
Route::post('/register', [RegisterController::class, 'store']);

Route::get('/login', [LoginController::class, 'create']);
Route::post('/login', [LoginController::class, 'store']);
Route::post('/logout', [LoginController::class, 'destroy']);

Route::get('/', [PostController::class, 'index']);

Route::middleware('auth')->group(function () {
    Route::post('/posts', [PostController::class, 'store']);
    Route::delete('/posts/{post}', [PostController::class, 'destroy']);
    Route::get('/posts/{post}/edit', [PostController::class, 'edit']);
    Route::patch('/posts/{post}', [PostController::class, 'update']);
    //
    Route::post('/posts/{post}/comments', [CommentController::class, 'store']);
    Route::get('/comments/{comment}/edit', [CommentController::class, 'edit']);
    Route::patch('/comments/{comment}', [CommentController::class, 'update']);
    Route::delete('/comments/{comment}', [CommentController::class, 'destroy']);
});


Route::get('/posts/{post}', [PostController::class, 'show']);


<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use App\Http\Controllers\LoginController;
use App\Http\Controllers\RegisterController;
use App\Http\Controllers\PostController;
use App\Http\Controllers\CommentController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\ConversationController;
use App\Http\Controllers\MessageController;
use App\Http\Controllers\ReactionController;
use App\Http\Controllers\NotificationController;

Route::post('/register', [RegisterController::class, 'apiRegister']);
Route::post('/login', [LoginController::class, 'apiLogin']);
Route::get('/posts', [PostController::class, 'index']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [LoginController::class, 'apiLogout']);

    Route::post('/posts', [PostController::class, 'apiPost']);
    Route::delete('/posts/{post}', [PostController::class, 'apiPostDelete']);
    Route::get('/posts/{post}', [PostController::class, 'apiPostShow']);
    Route::get('/posts/{post}/edit', [PostController::class, 'apiPostEdit']);
    Route::patch('/posts/{post}', [PostController::class, 'apiPostUpdate']);

    Route::post('/posts/{post}/comments', [CommentController::class, 'apiComment']);
    Route::delete('/comments/{comment}', [CommentController::class, 'apiCommentDelete']);
    Route::get('/comments/{comment}/edit', [CommentController::class, 'apiCommentEdit']);
    Route::patch('/comments/{comment}', [CommentController::class, 'apiCommentUpdate']);


    Route::get('/user', function (Request $request) {
        return $request->user();
    });
    Route::get('/users/{user}', [UserController::class, 'apiShowUser']);
    Route::patch('/users/{user}', [UserController::class, 'apiUpdateUser']);

    Route::get('/conversations', [ConversationController::class, 'index']);
    Route::post('/conversations', [ConversationController::class, 'store']);
    Route::get('/conversations/{conversation}', [ConversationController::class, 'show']);
    Route::put('/conversations/{conversation}/read', [ConversationController::class, 'markAsRead']);
    Route::delete('/conversations/{conversation}', [ConversationController::class, 'destroy']);

    Route::post('/conversations/{conversation}/messages', [MessageController::class, 'store']);

    Route::get('/reactions', [ReactionController::class, 'index']);
    Route::post('/react', [ReactionController::class, 'react']);

    Route::get('/notifications', [NotificationController::class, 'index']);
    Route::patch('/notifications/{id}/read', [NotificationController::class, 'markAsRead']);
    Route::patch('/notifications/read-all', [NotificationController::class, 'markAllAsRead']);

});

<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\ConversationController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\PostController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::view('/', 'home')->name('home');

Route::middleware('guest')->group(function () {
    Route::get('/register', [RegisteredUserController::class, 'create'])->name('register');
    Route::post('/register', [RegisteredUserController::class, 'store']);
    Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('login');
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');
    Route::get('/dashboard', DashboardController::class)->name('dashboard');
    Route::get('/perfiles/{user}', [ProfileController::class, 'show'])->name('profile.show');
    Route::get('/mi-perfil/editar', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('/mi-perfil', [ProfileController::class, 'update'])->name('profile.update');
    Route::middleware('verified')->group(function () {
        Route::post('/publicaciones', [PostController::class, 'store'])->name('posts.store');
        Route::get('/mensajes', [ConversationController::class, 'index'])->name('conversations.index');
        Route::post('/mensajes/iniciar', [ConversationController::class, 'start'])->name('conversations.start');
        Route::get('/mensajes/{conversation}', [ConversationController::class, 'show'])->name('conversations.show');
        Route::post('/mensajes/{conversation}', [ConversationController::class, 'store'])->name('conversations.messages.store');
    });
});

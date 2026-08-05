<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\ConversationController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\JobProposalController;
use App\Http\Controllers\PostController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ServiceOrderController;
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
        Route::get('/solicitudes/{jobRequest}/propuestas', [JobProposalController::class, 'index'])->name('job-proposals.index');
        Route::post('/solicitudes/{jobRequest}/propuestas', [JobProposalController::class, 'store'])->name('job-proposals.store');
        Route::patch('/solicitudes/{jobRequest}/propuestas/{proposal}/aceptar', [JobProposalController::class, 'accept'])->name('job-proposals.accept');
        Route::patch('/solicitudes/{jobRequest}/propuestas/{proposal}/rechazar', [JobProposalController::class, 'reject'])->name('job-proposals.reject');
        Route::patch('/solicitudes/{jobRequest}/propuestas/{proposal}/retirar', [JobProposalController::class, 'withdraw'])->name('job-proposals.withdraw');
        Route::get('/trabajos', [ServiceOrderController::class, 'index'])->name('orders.index');
        Route::get('/trabajos/{order}', [ServiceOrderController::class, 'show'])->name('orders.show');
        Route::patch('/trabajos/{order}/iniciar', [ServiceOrderController::class, 'start'])->name('orders.start');
        Route::patch('/trabajos/{order}/entregar', [ServiceOrderController::class, 'deliver'])->name('orders.deliver');
        Route::patch('/trabajos/{order}/completar', [ServiceOrderController::class, 'complete'])->name('orders.complete');
        Route::patch('/trabajos/{order}/cancelar', [ServiceOrderController::class, 'cancel'])->name('orders.cancel');
    });
});

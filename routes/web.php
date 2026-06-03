<?php

use App\Http\Controllers\Admin\AlbumController as AdminAlbumController;
use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\ImageController;
use App\Http\Controllers\Admin\TextBoxController;
use App\Http\Controllers\Admin\LocationController;
use App\Http\Controllers\AlbumController;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'Welcome')->name('home');

Route::prefix('admin')->middleware(['auth', 'verified'])->group(function () {
    Route::inertia('dashboard', 'Dashboard')->name('dashboard');
    Route::permanentRedirect('/', 'admin/dashboard');

    Route::name('admin.')->group(function () {
        Route::resource('locations', LocationController::class)->except(['create', 'edit']);
        Route::resource('categories', CategoryController::class)->except(['create', 'edit']);
        Route::resource('albums', AdminAlbumController::class)->except(['show']);
        Route::post('albums/{album}/toggle-published', [AdminAlbumController::class, 'togglePublished'])->name('albums.toggle-published');
        Route::resource('texts', TextBoxController::class)->only(['store', 'update']);
        Route::post('images/check-duplicates', [ImageController::class, 'checkDuplicates'])->name('images.check-duplicates');
        Route::resource('images', ImageController::class)->only(['index', 'store', 'update', 'destroy']);
    });
});

Route::get('/album/{album:slug}', [AlbumController::class, 'show'])->name('album.show');

require __DIR__.'/settings.php';

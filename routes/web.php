<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    if (auth()->check()) {
        return redirect()->route('myFiles');
    }
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
});

Route::controller(\App\Http\Controllers\FileController::class)
    ->middleware(['auth', 'verified'])
    ->group(function () {
        Route::get('/my-files/{folder?}','myFiles')
        ->where('folder','.*')
        ->name('myFiles');
        Route::get('/favourites', 'favourites')->name('favourites');
        Route::get('/trash', 'trash')->name('trash');
        Route::post('/folder/create','createFolder')->name('folder.create');
        Route::post('/file', 'store')->name('file.store');
        Route::delete('/file', 'destroy')->name('file.delete');
        Route::post('/file/restore', 'restore')->name('file.restore');
        Route::delete('/file/delete-forever', 'deleteForever')->name('file.deleteForever');
        Route::post('/file/add-to-favourites', 'addToFavourites')->name('file.addToFavourites');
        Route::get('/shared-with-me', 'sharedWithMe')->name('file.sharedWithMe');
        Route::get('/shared-by-me', 'sharedByMe')->name('file.sharedByMe');
        Route::post('/file/share', 'share')->name('file.share');        
        Route::get('/file/download', 'download')->name('file.download');
        Route::get('/file/download-shared-with-me', 'downloadSharedWithMe')->name('file.downloadSharedWithMe');
        Route::get('/file/download-shared-by-me', 'downloadSharedByMe')->name('file.downloadSharedByMe');
        Route::post('/files/move', 'move')->name('files.move');

    });

Route::controller(\App\Http\Controllers\FolderController::class)
    ->middleware(['auth', 'verified'])
    ->group(function () {
        Route::get('/folders/{folder}/subfolders', 'getSubFolders')->name('folders.subfolders');
    });

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';

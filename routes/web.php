<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use App\Http\Controllers\NotemateController;
use App\Http\Controllers\FileChangesController;
use App\Http\Controllers\SnippetFeaturesController;
use App\Http\Controllers\FramefilesController;
use App\Http\Controllers\CodelinesController;
use App\Http\Controllers\FiletreeController;

Route::get('/', function () {
    return Inertia::render('Welcome');
})->name('home');

Route::get('dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');


Route::get('notemate', function () {
	return Inertia::render('Notemate');
})->middleware(['auth', 'verified'])->name('notemate');

Route::get('notemate/codefolders', [NotemateController::class, 'getCodefolders'])
    ->middleware(['auth', 'verified'])->name('notemate.codefolders.index');

Route::post('notemate/codefolders', [NotemateController::class, 'storeCodefolder'])
    ->middleware(['auth', 'verified'])->name('notemate.codefolders.store');

Route::put('notemate/codefolders/{id}/set-working', [NotemateController::class, 'setWorkingFolder'])
    ->middleware(['auth', 'verified'])->name('notemate.codefolders.setWorking');

Route::get('notemate/file-changes', [FileChangesController::class, 'index'])
    ->middleware(['auth', 'verified'])->name('notemate.filechanges.index');

Route::post('notemate/file-changes', [FileChangesController::class, 'store'])
    ->name('notemate.filechanges.store');

Route::post('notemate/snippetfeatures', [SnippetFeaturesController::class, 'store'])
    ->middleware(['auth', 'verified'])->name('notemate.snippetfeatures.store');

Route::get('notemate/snippetfeatures', [SnippetFeaturesController::class, 'index'])
    ->middleware(['auth', 'verified'])->name('notemate.snippetfeatures.index');

Route::post('notemate/framefiles/process', [FramefilesController::class, 'process'])
    ->middleware(['auth', 'verified'])->name('notemate.framefiles.process');

Route::post('notemate/codelines/process', [CodelinesController::class, 'process'])
    ->middleware(['auth', 'verified'])->name('notemate.codelines.process');

 // Filetree routes
 Route::post('make/filetree', [FiletreeController::class, 'store'])
		 ->middleware(['auth', 'verified'])->name('filetree.store');

 Route::get('filetree', [FiletreeController::class, 'index'])
		 ->middleware(['auth', 'verified'])->name('filetree.index');

 Route::get('filetree/{projectFolderName}/{count?}',[FiletreeController::class, 'show'])
		 ->middleware(['auth','verified'])->name('filetree.show');



require __DIR__.'/settings.php';
require __DIR__.'/auth.php';

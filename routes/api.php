<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\NotemateController;
use App\Http\Controllers\FileChangesController;

Route::get('notemate/codefolders', [NotemateController::class, 'getCodefolders'])
    ->name('api.notemate.codefolders.index');

Route::post('notemate/file-changes', [FileChangesController::class, 'store'])
    ->name('api.notemate.filechanges.store');
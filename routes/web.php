<?php

use App\Http\Controllers\Public\PublicDocumentVerificationController;
use App\Http\Controllers\StorageProxyController;
use Illuminate\Support\Facades\Route;

Route::get('/public/documents/verify', [PublicDocumentVerificationController::class, 'verify'])
    ->middleware('throttle:public-verify');

Route::get('/storage/{path}', [StorageProxyController::class, 'show'])
    ->where('path', '.*')
    ->middleware('throttle:60,1');

Route::post('/logout', function () {
    if (auth()->check()) {
        auth()->user()->tokens()->delete();
        auth()->logout();
    }

    return redirect('/login');
});

Route::get('/login', function () {
    return view('app');
});

Route::get('/{any?}', function () {
    if (!auth()->check()) {
        return redirect('/login');
    }

    return view('app');
})->where('any', '.*');

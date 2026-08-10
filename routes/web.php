<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Public\PublicDocumentVerificationController;

Route::get('/public/documents/verify', [PublicDocumentVerificationController::class, 'verify']);

Route::get('/logout', function () {
    if (auth()->check()) {
        auth()->user()->tokens()->delete();
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

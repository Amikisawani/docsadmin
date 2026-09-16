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

// La SPA s'authentifie via un token Sanctum (localStorage), pas via la session
// web. Un redirect auth()->check() ici renvoie les utilisateurs connectés vers
// /login à chaque rechargement de `/`, et Vue les renvoie ensuite au tableau
// de bord : la page d'accueil s'actualise en boucle. L'accès réel reste protégé
// par auth:sanctum sur /api/v1 et par les gardes Vue Router.
Route::get('/{any?}', function () {
    return view('app');
})->where('any', '.*');

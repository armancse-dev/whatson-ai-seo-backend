<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});

Route::get('/tester', function () {
    return view('tester');
});

// Expose CSRF cookie for SPA if using cookie-based Sanctum auth
Route::get('/sanctum/csrf-cookie', [\App\Http\Controllers\Api\AuthController::class,'csrf']);

// Optional cookie-based auth endpoints (web middleware)
Route::post('/register', [\App\Http\Controllers\Api\AuthController::class,'register']);
Route::post('/login', [\App\Http\Controllers\Api\AuthController::class,'login']);
Route::post('/logout', [\App\Http\Controllers\Api\AuthController::class,'logout'])->middleware('auth:sanctum');
Route::get('/user', function (Illuminate\Http\Request $request) { return $request->user(); })->middleware('auth:sanctum');

// Login view
Route::get('/login', function () { return view('login'); });
// Register view
Route::get('/register', function () { return view('register'); });

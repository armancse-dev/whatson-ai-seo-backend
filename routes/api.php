<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\KeywordClusterController;
/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::post('/register', [AuthController::class,'register']);
Route::post('/login', [AuthController::class,'login']);

Route::middleware('auth:sanctum')->group(function(){
    Route::post('/logout',[AuthController::class,'logout']);
    Route::apiResource('projects', \App\Http\Controllers\Api\ProjectController::class);
    Route::post('projects/{project}/run-audit', [\App\Http\Controllers\Api\AuditController::class,'run']);
    Route::post('/projects/{project}/clusters/start', [KeywordClusterController::class,'start']);
    // Add keywords ingestion endpoint for a project
    Route::post('/projects/{project}/keywords', [KeywordClusterController::class,'store']);
    // List project keywords
    Route::get('/projects/{project}/keywords', [KeywordClusterController::class,'index']);
    Route::get('/projects/{project}/reports', [\App\Http\Controllers\Api\ReportController::class,'index']);
});
Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});







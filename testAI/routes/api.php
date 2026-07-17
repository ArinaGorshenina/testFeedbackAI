<?php

use App\Http\Controllers\Api\ContactController;
use App\Http\Controllers\Api\StatusController;
use Illuminate\Support\Facades\Route;

Route::middleware('throttle:contact')->post('/contact', [ContactController::class, 'store']);
Route::get('/health', [StatusController::class, 'health']);
Route::get('/metrics', [StatusController::class, 'metrics']);

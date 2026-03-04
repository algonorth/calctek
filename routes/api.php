<?php

use App\Http\Controllers\Api\CalculationController;
use Illuminate\Support\Facades\Route;

Route::post('/calculate', [CalculationController::class, 'calculate']);
Route::get('/calculations', [CalculationController::class, 'index']);
Route::delete('/calculations/{calculation}', [CalculationController::class, 'destroy']);
Route::delete('/calculations', [CalculationController::class, 'destroyAll']);

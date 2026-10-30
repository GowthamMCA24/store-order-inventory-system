<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\OrderController;


Route::post('/orders', [OrderController::class, 'store']);
Route::get('/customer/orders', [OrderController::class, 'customerOrders']);
Route::get('/products/low-stock', [OrderController::class, 'lowStock']);

<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\FedEx\RequestLabel;
use App\Http\Controllers\Webflow\GetCustomer;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::post('/request-label', [RequestLabel::class, 'index'])->name('fedex-label');
Route::get('/customer-detail', [GetCustomer::class, 'index'])->name('customer-detail');

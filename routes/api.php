<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\FedEx\RequestLabel;
use App\Http\Controllers\FedEx\Location;
use App\Http\Controllers\FedEx\CreateNewLabel;
use App\Http\Controllers\Webflow\GetCustomer;
use App\Http\Controllers\Documents\Upload;
use App\Http\Controllers\Customer\Referral;
use App\Http\Controllers\Customer\Profile;
use App\Http\Controllers\Customer\Government;
use App\Http\Controllers\Customer\Payment;
use App\Http\Controllers\Customer\ReferralDetail;
use App\Http\Controllers\Customer\GovernmentDetail;
use App\Http\Controllers\Customer\PaymentDetail;
use App\Http\Controllers\Customer\Support;
use App\Http\Controllers\Customer\GoldPacks;
use App\Http\Controllers\Customer\UploadDocs;
use App\Http\Controllers\Customer\Statistic;
use App\Http\Controllers\Documents\Delete;
use App\Http\Controllers\Customer\EmailController;



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

// Support
Route::post('/support/{customer_id}', [Support::class, 'index'])->name('support');

// Request Label
Route::post('/label', [RequestLabel::class, 'index'])->name('request-label');

// Create New Label
Route::get('/label/{customer_id}', [CreateNewLabel::class, 'index'])->name('create-label');

// Upload Documents
Route::post('/document/{customer_id}', [Upload::class, 'index'])->name('customer-document');

// Referrals
Route::post('/referral/{customer_id}', [Referral::class, 'index'])->name('referral');

// Update Profile
Route::put('/customer/{customer_id}', [Profile::class, 'index'])->name('customer-profile');

// Government ID
Route::post('/government/{customer_id}', [Government::class, 'index'])->name('customer-government');

// Payment Option
Route::post('/payment/{customer_id}', [Payment::class, 'index'])->name('customer-payment');

// Customer Details
Route::get('/customer', [GetCustomer::class, 'index'])->name('customer-detail');

// Referral Details
//Route::get('/referral/{customer_id}', [ReferralDetail::class, 'index'])->name('referral-detail');

// Payment Details
//Route::get('/payment/{customer_id}', [PaymentDetail::class, 'index'])->name('payment-detail');

// Government Details
//Route::get('/government/{customer_id}', [GovernmentDetail::class, 'index'])->name('government-detail');

// Get All Gold Packs 
Route::get('/gold-packs/{customer_id}', [GoldPacks::class, 'index'])->name('gold-packs');

// Get All Docs 
Route::get('/document/{customer_id}', [UploadDocs::class, 'index'])->name('document');


// Get All Docs 
Route::get('/statics/{customer_id}', [Statistic::class, 'index'])->name('statics');


Route::delete('/storage/documents/{file}', [Delete::class, 'index'])->name('delete');

Route::post('/location', [Location::class, 'index'])->name('location');

Route::get('/send-email', [EmailController::class, 'index'])->name('send-mail');
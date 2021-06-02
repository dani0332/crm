<?php

use App\Http\Controllers\AuditableController;
use App\Http\Controllers\CarQuoteController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\HealthQuoteController;
use App\Http\Controllers\PartnerController;
use App\Http\Controllers\RewardCategoryController;
use App\Http\Controllers\RewardController;
use App\Http\Controllers\RewardTagController;
use App\Http\Controllers\RewardTranslationController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\InsuranceCompanyController;
use App\Http\Controllers\HandlerController;
use App\Http\Controllers\ReasonController;
use App\Http\Controllers\StatusController;
use App\Http\Controllers\PaymentModeController;
use App\Http\Controllers\TransactionController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
 */

Route::get('/', function () {
    return redirect('login');
});

Route::get('auth/google', 'App\Http\Controllers\GoogleSocialiteController@redirectToGoogle');
Route::get('google/callback', 'App\Http\Controllers\GoogleSocialiteController@handleCallback');

Route::middleware(['auth:sanctum', 'verified'])
    ->get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

Route::post('dashboard-stats', [DashboardController::class, 'dashboardStats']);
Route::group(['prefix' => 'rewards'], function () {
    Route::resource('partner', PartnerController::class);
    Route::resource('reward', RewardController::class);
    Route::resource('reward-categories', RewardCategoryController::class);
    Route::resource('reward-tags', RewardTagController::class);
    Route::resource('reward.reward-translation', RewardTranslationController::class);
});

Route::group(['prefix' => 'admin'], function () {
    Route::resource('users', UserController::class);
    Route::resource('roles', RoleController::class);
});

Route::group(['prefix' => 'quotes'], function () {
    Route::resource('carquotes', CarQuoteController::class);
    Route::POST('carquotes/resubmit_api', [CarQuoteController::class, 'resubmitApi']);
    Route::resource('healthquotes', HealthQuoteController::class);
});


Route::group(['prefix' => 'transapp'], function () {
    Route::resource('insurancecompany', InsuranceCompanyController::class);
    Route::resource('handler', HandlerController::class);
    Route::resource('reason',ReasonController::class);
    Route::resource('status',StatusController::class);
    Route::resource('paymentmode',PaymentModeController::class);
    Route::resource('transaction',TransactionController::class);
});

Route::resource('customer', CustomerController::class);

Route::post('auditable', [AuditableController::class, 'loadAuditableComponent']);

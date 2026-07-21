<?php

use App\Http\Controllers\PublicLeaveRequestController;
use Illuminate\Support\Facades\Route;

Route::middleware('throttle:10,1')->prefix('leave-request')->name('leave-request.')->group(function () {
    Route::get('/{companyCode}', [PublicLeaveRequestController::class, 'lookupPage'])->name('lookup');
    Route::post('/{companyCode}/lookup', [PublicLeaveRequestController::class, 'lookup'])->name('lookup.submit');
    Route::post('/{companyCode}/submit', [PublicLeaveRequestController::class, 'submit'])->name('submit');
});

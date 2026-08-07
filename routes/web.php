<?php

use App\Http\Controllers\PublicLeaveRequestController;
use Illuminate\Support\Facades\Route;

Route::get('/welcome', function () {
    return view('welcome');
})->name('welcome');

Route::middleware('throttle:30,1')->prefix('leave-request')->name('leave-request.')->group(function () {
    Route::get('/{companyCode}', [PublicLeaveRequestController::class, 'lookupPage'])->name('lookup');
    Route::post('/{companyCode}/lookup', [PublicLeaveRequestController::class, 'lookup'])->name('lookup.submit');
    Route::get('/{companyCode}/lookup', function (string $companyCode) {
        return redirect()->route('leave-request.lookup', $companyCode);
    });
    Route::post('/{companyCode}/submit', [PublicLeaveRequestController::class, 'submit'])->name('submit');
    Route::get('/attachment/{leaveRequest}', [PublicLeaveRequestController::class, 'attachment'])->name('attachment');
});


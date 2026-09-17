<?php

use App\Http\Controllers\QuestionController;
use Illuminate\Support\Facades\Route;

Route::view('/', 'landing')->name('landing');

// Bound by the unguessable token, not the id: this page is public, so an id here
// would let anyone who scans one QR code enumerate every question and answer.
Route::get('/question/{question:qr_token}', [QuestionController::class, 'show'])->name('question.show');

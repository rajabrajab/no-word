<?php

use App\Http\Controllers\QuestionController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->to('admin');
});

Route::get('/question/{question}', [QuestionController::class, 'show'])->name('question.show');

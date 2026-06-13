<?php

use Illuminate\Support\Facades\Route;
use Modules\Classroom\Http\Controllers\ClassroomController;

Route::get('/classes', [ClassroomController::class, 'index'])->name('classroom.index');
Route::get('/classes/{classOffering}', [ClassroomController::class, 'show'])->name('classroom.show');

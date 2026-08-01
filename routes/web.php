<?php

use App\Http\Controllers\ArticleController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\CourseController;
use App\Http\Controllers\CourseTypeController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\TrainerController;
use Illuminate\Support\Facades\Route;

// Veřejný frontend
Route::get('/', [HomeController::class, 'index'])->name('home');

// Jména rout jsou sdílené jádro a musí zůstat beze změny (P3), URL segmenty
// jsou per-web obsah — viz config('site.routes.*') a DECISIONS.md.
Route::get('/'.config('site.routes.courses'), [CourseController::class, 'index'])->name('courses.index');
Route::get('/'.config('site.routes.course_types').'/{typeSlug}', [CourseTypeController::class, 'show'])->name('course_types.show');
Route::get('/'.config('site.routes.courses').'/{typeSlug}/{courseSlug}', [CourseController::class, 'show'])->name('courses.show');

Route::get('/clanky', [ArticleController::class, 'index'])->name('articles.index');
Route::get('/clanky/{slug}', [ArticleController::class, 'show'])->name('articles.show');

Route::get('/'.config('site.routes.trainers'), [TrainerController::class, 'index'])->name('trainers.index');
Route::get('/'.config('site.routes.trainers').'/{slug}', [TrainerController::class, 'show'])->name('trainers.show');

Route::get('/kontakt', [ContactController::class, 'index'])->name('contact.index');
Route::post('/kontakt', [ContactController::class, 'send'])->name('contact.send');

<?php

use App\Http\Controllers\ArticleController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\CourseController;
use App\Http\Controllers\CourseTypeController;
use App\Http\Controllers\GalleryController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\TrainerController;
use Illuminate\Support\Facades\Route;

// Veřejný frontend
Route::get('/', [HomeController::class, 'index'])->name('home');

// Jména rout jsou sdílené jádro a musí zůstat beze změny (P3), URL segmenty
// jsou per-web obsah — viz config('site.routes.*') a DECISIONS.md.
Route::get('/'.config('site.routes.courses'), [CourseController::class, 'index'])->name('courses.index');
Route::get('/'.config('site.routes.course_types').'/{typeSlug}', [CourseTypeController::class, 'show'])->name('course_types.show');
Route::get('/'.config('site.routes.courses').'/{typeSlug}/{courseSlug}', [CourseController::class, 'show'])->name('courses.show');

// Fallback 'clanky' drží dosavadní chování webů, které klíč v configu
// ještě nemají — nová konfigurovatelnost nic nerozbije.
Route::get('/'.config('site.routes.articles', 'clanky'), [ArticleController::class, 'index'])->name('articles.index');
Route::get('/'.config('site.routes.articles', 'clanky').'/{slug}', [ArticleController::class, 'show'])->name('articles.show');

Route::get('/'.config('site.routes.trainers'), [TrainerController::class, 'index'])->name('trainers.index');
Route::get('/'.config('site.routes.trainers').'/{slug}', [TrainerController::class, 'show'])->name('trainers.show');

Route::get('/'.config('site.routes.galleries', 'galerie'), [GalleryController::class, 'index'])->name('galleries.index');
Route::get('/'.config('site.routes.galleries', 'galerie').'/{slug}', [GalleryController::class, 'show'])->name('galleries.show');

Route::get('/'.config('site.routes.search', 'hledat'), [SearchController::class, 'index'])->name('search');

Route::get('/kontakt', [ContactController::class, 'index'])->name('contact.index');
Route::post('/kontakt', [ContactController::class, 'send'])->name('contact.send');

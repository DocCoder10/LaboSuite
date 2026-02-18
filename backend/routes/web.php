<?php

use App\Http\Controllers\AnalysisController;
use App\Http\Controllers\CatalogController;
use App\Http\Controllers\LocaleController;
use App\Http\Controllers\SettingController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/analyses');

Route::get('/lang/{locale}', [LocaleController::class, 'switch'])->name('locale.switch');

Route::get('/analyses', [AnalysisController::class, 'index'])->name('analyses.index');
Route::get('/analyses/new', [AnalysisController::class, 'create'])->name('analyses.create');
Route::post('/analyses', [AnalysisController::class, 'store'])->name('analyses.store');
Route::get('/analyses/{analysis}', [AnalysisController::class, 'show'])->name('analyses.show');
Route::get('/analyses/{analysis}/print', [AnalysisController::class, 'print'])->name('analyses.print');

Route::get('/catalog', [CatalogController::class, 'index'])->name('catalog.index');
Route::post('/catalog/disciplines', [CatalogController::class, 'storeDiscipline'])->name('catalog.disciplines.store');
Route::post('/catalog/categories', [CatalogController::class, 'storeCategory'])->name('catalog.categories.store');
Route::post('/catalog/subcategories', [CatalogController::class, 'storeSubcategory'])->name('catalog.subcategories.store');
Route::post('/catalog/parameters', [CatalogController::class, 'storeParameter'])->name('catalog.parameters.store');
Route::delete('/catalog/parameters/{parameter}', [CatalogController::class, 'destroyParameter'])->name('catalog.parameters.destroy');

Route::get('/settings', [SettingController::class, 'edit'])->name('settings.edit');
Route::put('/settings', [SettingController::class, 'update'])->name('settings.update');

Route::get('/health', function () {
    return response('OK', 200);
});

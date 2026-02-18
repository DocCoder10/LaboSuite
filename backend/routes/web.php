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
Route::post('/analyses/new/selection', [AnalysisController::class, 'storeSelection'])->name('analyses.selection.store');
Route::get('/analyses/new/results', [AnalysisController::class, 'results'])->name('analyses.results');
Route::post('/analyses', [AnalysisController::class, 'store'])->name('analyses.store');
Route::get('/analyses/{analysis}', [AnalysisController::class, 'show'])->name('analyses.show');
Route::get('/analyses/{analysis}/print', [AnalysisController::class, 'print'])->name('analyses.print');

Route::get('/catalog', [CatalogController::class, 'index'])->name('catalog.index');
Route::post('/catalog/disciplines', [CatalogController::class, 'storeDiscipline'])->name('catalog.disciplines.store');
Route::put('/catalog/disciplines/{discipline}', [CatalogController::class, 'updateDiscipline'])->name('catalog.disciplines.update');
Route::delete('/catalog/disciplines/{discipline}', [CatalogController::class, 'destroyDiscipline'])->name('catalog.disciplines.destroy');
Route::post('/catalog/categories', [CatalogController::class, 'storeCategory'])->name('catalog.categories.store');
Route::put('/catalog/categories/{category}', [CatalogController::class, 'updateCategory'])->name('catalog.categories.update');
Route::delete('/catalog/categories/{category}', [CatalogController::class, 'destroyCategory'])->name('catalog.categories.destroy');
Route::post('/catalog/subcategories', [CatalogController::class, 'storeSubcategory'])->name('catalog.subcategories.store');
Route::put('/catalog/subcategories/{subcategory}', [CatalogController::class, 'updateSubcategory'])->name('catalog.subcategories.update');
Route::delete('/catalog/subcategories/{subcategory}', [CatalogController::class, 'destroySubcategory'])->name('catalog.subcategories.destroy');
Route::post('/catalog/parameters', [CatalogController::class, 'storeParameter'])->name('catalog.parameters.store');
Route::put('/catalog/parameters/{parameter}', [CatalogController::class, 'updateParameter'])->name('catalog.parameters.update');
Route::delete('/catalog/parameters/{parameter}', [CatalogController::class, 'destroyParameter'])->name('catalog.parameters.destroy');

Route::get('/settings', [SettingController::class, 'edit'])->name('settings.edit');
Route::put('/settings', [SettingController::class, 'update'])->name('settings.update');

Route::get('/health', function () {
    return response('OK', 200);
});

<?php
function createCrudRoute($controller) {
    Route::get('/', $controller . '@getRows');
    Route::get('/create', $controller . '@create');
    Route::get('/export', $controller . '@export');
    Route::get('/{id}', $controller . '@show');
    Route::get('/{id}/edit', $controller . '@edit');
    Route::post('/', $controller . '@store');
    Route::post('/search', $controller . '@search');
    Route::post('/delete-rows', $controller . '@deleteRows');
    Route::put('/{id}', $controller . '@update');
    Route::delete('/{id}', $controller . '@destroy');
}
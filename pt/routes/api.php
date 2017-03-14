<?php

use Illuminate\Http\Request;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:api');

Route::get('/test', function(){
    return response()->json([
        'user' => [
            'first_name' => 'Kennedy',
            'last_name' => 'Otis'
        ]
    ]);
});

Route::group(['middleware' => 'auth:api'], function(){
    Route::resource('products', 'ProductController');
    Route::resource('roles', 'RoleController');
    Route::resource('programs', 'ProgramController');
    Route::any('programs/{id}/restore','ProgramController@restore');
});
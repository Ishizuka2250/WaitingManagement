<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/
Route::get('/app/', [App\Http\Controllers\AppController::class, 'index']);
Route::get('/app/{any}', [App\Http\Controllers\AppController::class, 'index'])->where('any', '.*');
Route::get('/readme/', function() {return view('readme');});
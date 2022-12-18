<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Minesweeper;

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

Route::get('/', function () {
    return view('welcome');
});

Route::get('/spec1', [Minesweeper::class, 'specOneAndTwo']);
Route::get('/spec2', [Minesweeper::class, 'specOneAndTwo']);
Route::get('/game', [Minesweeper::class, 'game']);
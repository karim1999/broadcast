<?php

use Illuminate\Support\Facades\Broadcast;
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

//Route::get('/', function () {
//    return view('welcome');
//});
//Route::get('/admin', function () {
//    return view('admin');
//});
Route::middleware(['auth'])->group(function () {
//    Route::get('/send', function () {
//        \App\Events\SendMessage::dispatch("Hello");
//        return "Sent";
//    });

    Route::get('/', function () {
        return view('dashboard');
    })->name('dashboard');

    Route::get('/admin', function () {
        return view('dashboard', ["is_admin" => true]);
    });

});

require __DIR__ . '/auth.php';

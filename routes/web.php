<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\VideoController;
use App\Http\Controllers\ProxyController;
use Illuminate\Http\Request;
/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});

//create roite for zoom.blade.php with data
Route::get('/zoom', function () {
    return view('zoom');
});
// Route::get('/deviceResetss', function () {
//     return view('securityError');
// });
Route::get('/StudentsDeviceReset', function () {
    return view('resetDevice');
});

Route::get('/deviceReset', function () {
    // Trigger a 500 internal server error response
    abort(500, 'Internal Server Error');
});

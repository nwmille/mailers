<?php


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

use App\Http\Controllers\ApMailer;
use App\Invoice;
use App\Role;
use Illuminate\Http\Request;
use App\Http\Controllers\Auth\RegisterController as Register;



Auth::routes();

Route::get('/login', function () {
    return view('login');
})->name('login')->middleware('guest');

Route::get('', function () {
    return view('login');
})->middleware('guest');

//Route::get('baz', function () {
//    $baz = new ApMailer();
//    $baz->checkApRules();
//    return view('baz');
//})->middleware('auth');

Route::get('/home', function () {
    return view('checkflow');
})->name('home')->middleware('auth');

Route::get('/viewPDF/{vendorId}/{invoiceNum}', function ($vendorId, $invoiceNum) {
    $query = Invoice::where('invoice_number', $invoiceNum)->where('vendor_id', $vendorId)->first();
    $fullPath = $query->file_dir."/".$query->file_name;
    $fileName = $query->file_name;
    return view('test', compact('fullPath', 'fileName'));
});


//Route::get('/checkrun/{checkKey}', 'Checkflow\Checkflow@retrieveCheckRun');


Route::get('/register', 'Auth\RegisterController@showRegistrationForm')->name('register')->middleware('guest');
Route::post('/register', 'Auth\RegisterController@register')->name('register')->middleware('guest');


Route::post('foo/bar', 'Checkflow\Checkflow@searchChecks')->middleware('auth');
Route::post('foo/baz', 'Checkflow\Checkflow@searchInvoices')->middleware('auth');
Route::post('foo/pdf', 'Checkflow\Checkflow@showPDF')->middleware('auth');



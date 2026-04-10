<?php

use App\Http\Controllers\API\ScanController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;

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
Route::get('/produit/{id}/qr', [ScanController::class, 'showQr']);
Route::get('/produit/{id}/barcode', function ($id) {
    $produit = \App\Models\Produit::findOrFail($id);
    return view('barcode', compact('produit'));
})->middleware('auth');

Route::get('/', function () {
    return redirect('api/documentation');
});



<?php

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

Route::get('/', function () {
    return view('welcome');
});


Route::get('/s', function () {
    $content = Storage::url('/users/artboard_2.png'); 

    dd($content);
});

Route::get('/images/{filename}', function ($filename)
{
    $path = storage_path('app/public/users/' . $filename);
    if(!file_exists($path)) abort(404);
    $file = file_get_contents($path);
    $type = mime_content_type($path);
    return $path;
    return response($file, 200)->header("Content-Type", $type);
});
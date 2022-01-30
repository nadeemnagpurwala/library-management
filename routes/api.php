<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UserController;
use App\Http\Controllers\BookController;

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

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::post('login', [UserController::class, 'authenticate']);
Route::post('register', [UserController::class, 'register']);

Route::group(['middleware' => ['jwt.verify']], function() {
    Route::get('logout', [UserController::class, 'logout']);
    Route::get('get_user', [UserController::class, 'getUser']);
    Route::post('edit_user/{user}', [UserController::class, 'editUser']);
    Route::delete('delete_user/{user}',  [UserController::class, 'destroy']);
    Route::get('get_books', [BookController::class, 'index']);
    Route::post('create_book', [BookController::class, 'store']);
    Route::post('edit_book/{book}', [BookController::class, 'editBook']);
    Route::get('get_book/{book}', [BookController::class, 'show']);
    Route::delete('delete_book/{book}',  [BookController::class, 'destroy']);
    Route::post('rent_book/{user}/{book}', [BookController::class, 'rentBook']);
    Route::post('return_book/{user}/{book}', [BookController::class, 'returnBook']);
    Route::post('user_books/{user}', [BookController::class, 'userBooks']);
});
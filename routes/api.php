<?php

use App\Http\Controllers\Admin\AllImageController;
use App\Http\Controllers\admin\DashboardController;
use App\Http\Controllers\Admin\ServiceController;
use App\Http\Controllers\AuthenticationController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Route::get('/user', function (Request $request) {
//     return $request->user();
// })->middleware('auth:sanctum');


Route::Post('authenticate',[AuthenticationController::class,'authenticate']);

Route::group(['middleware' => ['auth:sanctum']],function(){

    Route::get('admin/dashboard',[DashboardController::class,'index']);
    Route::get('/logout',[AuthenticationController::class,'logout']);
    // service route
    Route::post('/service/store',[ServiceController::class,'store']);
    Route::put('/service/update/{id}',[ServiceController::class,'update']);
    Route::get('/services/show',[ServiceController::class,'index']);
    Route::get('/service/{id}',[ServiceController::class,'show']);
    Route::delete('/service/delete/{id}',[ServiceController::class,'destroy']);
    // All Image route
    Route::post('/all-image/store',[AllImageController::class,'store']);
});
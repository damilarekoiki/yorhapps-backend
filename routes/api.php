<?php

use App\Http\Controllers\AppController;
use App\Http\Controllers\AppReleasesController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\VisitController;
use App\Http\Controllers\VisitorController;


use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

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

Route::middleware('auth:api')->get('/user', function (Request $request) {
    return $request->user();
});

// Route::group([

//     'middleware' => ['assign.guard:admin','auth:api'],
//     'prefix' => 'auth/admin'
 
// ], function ($router) {

//     Route::post('login', [AuthController::class, 'login'])->withoutMiddleware(['auth:api']);
//     Route::post('logout', [AuthController::class, 'logout']);
//     Route::post('refresh', [AuthController::class, 'refresh'])->withoutMiddleware(['auth:api']);
//     Route::get('user', [AuthController::class, 'me']);

// });

Route::group([

    'middleware' => ['api', 'assign.guard:admin'],
    'prefix' => 'auth/admin'

], function ($router) {

    Route::post('register', [AuthController::class, 'adminRegister']);
    Route::post('login', [AuthController::class, 'adminLogin']);
    Route::post('refresh', [AuthController::class, 'refresh']);
    Route::post('logout', [AuthController::class, 'logout']);
    Route::get('user', [AuthController::class, 'adminUser']);

});

Route::group([
    'middleware' => ['api', 'assign.guard:admin'],
    'prefix' => 'admin/app'
], function ($router) {
    Route::post('create_app', [AppController::class, 'createApp']);
    Route::get('fetch_all_apps', [AppController::class, 'fetchAllApps']);
    Route::post('scan_file', [AppController::class, 'scanFile']);
    Route::post('add_app_release', [AppReleasesController::class, 'addAppRelease']);
    Route::get('fetch_paginated_apps_grouped_by_category', [AppController::class, 'fetchPaginatedAppsGroupedByCategory']);
    Route::get('fetch_paginated_apps_in_category', [AppController::class, 'fetchPaginatedAppsInCategory']);
    Route::get('fetch_paginated_apps_in_category', [AppController::class, 'fetchPaginatedAppsInCategory']);

});

Route::group([
    'middleware' => ['api', 'assign.guard:api', 'create.visitor'],
    'prefix' => 'user/app'
], function ($router) {

    Route::get('fetch_paginated_apps_grouped_by_category', [AppController::class, 'fetchPaginatedAppsGroupedByCategory']);
    
    Route::get('fetch_all_paginated_apps', [AppController::class, 'fetchAllPaginatedApps']);

    Route::get('fetch_paginated_apps_in_category', [AppController::class, 'fetchPaginatedAppsInCategory']);

    Route::get('get_app_with_releases', [AppController::class, 'getAppWithReleases']);

    Route::get('get_app_with_releases_and_recommended', [AppController::class, 'getAppWithReleasesAndRecommended']);


    Route::get('get_device_details', [VisitorController::class, 'getDeviceDetails']);

    Route::post('create_visit', [VisitController::class, 'createVisit']);

    Route::get('download_release', [AppReleasesController::class, 'downloadRelease']);

    Route::get('search_app_release', [AppReleasesController::class, 'searchAppRelease']);

});

Route::get('/test_password', [AuthController::class, 'testPassword']);

// "tymon/jwt-auth": "^1.0"




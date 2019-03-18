<?php

use App\Models\Sheet;
use Illuminate\Http\Request;

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

Route::post('/sheets/webhook', function (Request $request) {
    $body = $request->post();

    foreach ($body['events'] as $event) {
        if ($event['name'] == 'channel_vacated') {
            $sheetId = str_replace('presence-sheet-', '', $event['channel']);
            $sheet = Sheet::find($sheetId);

            if ($sheet->isEmpty()) {
                $sheet->delete();
            }
        }

        http_response_code(200);
    }
});

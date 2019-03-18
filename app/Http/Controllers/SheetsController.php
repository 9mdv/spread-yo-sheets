<?php

namespace App\Http\Controllers;

use App\Models\Sheet;
use Pusher\Laravel\Facades\Pusher;
use Illuminate\Support\Facades\Auth;


class SheetsController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function newSheet()
    {
        $sheet = Sheet::create([
            'name' => 'Untitled spreadsheet',
            '_owner' => Auth::user()->_id,
            'content' => [[]]
        ]);

        return redirect(route('sheets.view', ['sheet' => $sheet]));
    }

    public function view(Sheet $sheet)
    {
        Auth::user()->push('viewed_sheets', $sheet->_id);

        return view('spreadsheet', ['sheet' => $sheet]);
    }

    public function update($id)
    {
        $sheet = Sheet::findOrFail($id);
        $change = request('change');
        [$rowIndex, $columnIndex, $oldValue, $newValue] = $change;

        $sheet->content = $this->updateCell($rowIndex, $columnIndex, $newValue, $sheet->content);
        $sheet->save();

        Pusher::trigger($sheet->channel_name, 'updated', ['change' => $change]);

        return response()->json(['sheet' => $sheet]);
    }

    public function authenticateForSubscription($id)
    {
        $authSignature = Pusher::presence_auth(
            request('channel_name'),
            request('socket_id'),
            Auth::user()->_id,
            Auth::user()->toArray()
        );
        return response()->json(json_decode($authSignature));
    }

    protected function updateCell($rowIndex, $columnIndex, $newValue, $sheetContent)
    {
        // we expand the sheet to reach the farthest cell
        for ($row = 0; $row <= $rowIndex; $row++) {
            // create the row if it doesnt exist
            if (!isset($sheetContent[$row])) {
                $sheetContent[$row] = [];
            }

            for ($column = 0; $column <= $columnIndex; $column++) {
                if (!isset($sheetContent[$row][$column])) {
                    // create the column if it doesnt exist
                    $sheetContent[$row][$column] = null;
                }
            }
        }

        $sheetContent[$rowIndex][$columnIndex] = $newValue;

        return $sheetContent;
    }
}

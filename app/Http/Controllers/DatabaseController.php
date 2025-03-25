<?php

namespace App\Http\Controllers;

use App\Http\Requests\database\ImportDataRequest;
use App\Services\Database\DatabaseService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class DatabaseController extends Controller
{

    private $databaseService;

    public function __construct(DatabaseService $databaseService){
        $this->databaseService = $databaseService;
    }

    public function goToPage()
    {
        return view('database.clear');
    }

    public function cleanDatabase(){
        try {
            $this->databaseService->cleanTables();
            Session()->flash('flash_message', __('Votre base de donées est nétoyée avec succès'));
        } catch (\Exception $e) {
            Session()->flash('flash_message_warning', __('Une erreur est survenue lors du nettoyage de la base de données'));
        }
        return redirect()->route('database.index');
    }

    public function goToImport() {
        $tables = $this->databaseService->getAllTables();
        return view("database.import", ["tables" => $tables, "errorImport" => session('errorImport'), "warningImport" => session('warningImport')]);
    }

    public function import(Request $request) {
        $validator = Validator::make($request->all(), [
           'file1' => 'required|file|mimes:csv,txt',
           'file2' => 'required|file|mimes:csv,txt',
           'file3' => 'required|file|mimes:csv,txt'
        ]);

        if($validator->fails()) {
            Session()->flash('flash_message_warning', __('Un des fichiers n\'a été importé'));
            return redirect()->back()->withErrors($validator);
        }

        [$error, $warning] = $this->databaseService->importDataToDB($request->file1, $request->file2, $request->file3);
        if(!$error) {
            Session()->flash('flash_message', __('Les données ont été importées avec succès'));
        } else {
            Session()->flash('flash_message_warning', __("Les données n'ont pas été importés"));
        }

        if(count($warning) > 0) return redirect()->back()->with('warningImport', $warning);
        if(count($error) > 0) return redirect()->back()->with('errorImport', $error);

        return redirect()->back();
    }

}
<?php

namespace App\Http\Controllers;

use App\Http\Requests\database\ImportDataRequest;
use App\Http\Requests\Request;
use App\Services\Database\DatabaseService;

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
        return view("database.import", ["tables" => $tables]);
    }

    public function import($external_id, Request $request) {
        $user = $this->findByExternalId($external_id);

        if( !auth()->user()->canChangePasswordOn($user) ) {
            unset($request['password']);
        }

        if($request->hasFile('file')) {
            $this->databaseService->importIndustry($request->file);
            Session()->flash('flash_message', __('Les données ont été importées avec succès'));
        } else {
            Session()->flash('flash_message_warning', __('Aucun fichier n\'a été importé'));
        }

        return redirect()->route('database.importPage');
    }

}
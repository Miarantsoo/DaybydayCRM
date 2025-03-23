<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Configuration;
use Illuminate\Http\Request;


class ConfigurationsController extends Controller
{

    public function setConfiguration(Request $request) {
        $conf = Configuration::where('desce', 'remise')->first();
        if($conf) {
            $conf->update(['val' => $request->val]);
        } else {
            Configuration::create(['desce' => 'remise', 'val' => $request->val], ['timestamps' => false]);
        }

        return response()->json("Remise configure avec succes");
    }

}
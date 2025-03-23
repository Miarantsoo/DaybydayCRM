<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Configuration extends Model
{
    //

    public $timestamps = false;

    protected $fillable = ['val', 'desce'];
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Billboard extends Model
{
    protected $table = 'billboard';

    public $timestamps = false;

    protected $fillable = [
        'title',
        'button',
        'url',
        'icon',
        'color',
    ];
}

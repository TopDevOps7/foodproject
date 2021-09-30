<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Projects extends Model
{
    public $table = "projects";

    public $fillable = [
        'id','name', 'numberofticket', 'priceofticker', 'progressval','available','sold','status','picture',
        'created_at', 'updated_at'
    ];

    public $dates = ['created_at', 'updated_at'];
    public $primaryKey = 'id';
}
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Shop extends Model
{
    protected $fillable =[
        'name',
        'address',
        'owner_id'
    ];

    public function owner(){
        return $this->hasOne(User::class, 'id', 'owner_id');
    }
}

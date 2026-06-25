<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Unit extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'm_unit';

    protected $fillable = [
        'code',
        'nama_unit',
    ];

    public function userJabatanUnits()
    {
        return $this->hasMany(UserJabatanUnit::class, 'unit_id', 'id');
    }
}

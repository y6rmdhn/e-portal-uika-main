<?php
// app/Models/DataPribadi.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DataPribadi extends Model
{
    protected $connection = 'pgsql';
    protected $table = 'tb_data_pribadi';
    protected $primaryKey = 'dp_id';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = false;
    protected $fillable = ['dp_id', 'user_id', 'nama_lengkap', 'nik', 'instansi_ext', 'email'];
}

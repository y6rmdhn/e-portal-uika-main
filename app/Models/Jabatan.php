<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Permission\Models\Role as SpatieRole;

class Jabatan extends SpatieRole
{
    use HasFactory, SoftDeletes;

    protected $table = 'm_jabatan';

    protected $fillable = [
        'name',
        'nama_jabatan',
        'guard_name',
    ];

    /**
     * Set the name attribute.
     * Keep name and nama_jabatan in sync.
     */
    public function setNameAttribute($value)
    {
        $this->attributes['name'] = $value;
        $this->attributes['nama_jabatan'] = $value;
    }

    /**
     * Set the nama_jabatan attribute.
     * Keep name and nama_jabatan in sync.
     */
    public function setNamaJabatanAttribute($value)
    {
        $this->attributes['nama_jabatan'] = $value;
        $this->attributes['name'] = $value;
    }

    /**
     * Accessor for nama_jabatan fallback to name if empty.
     */
    public function getNamaJabatanAttribute($value)
    {
        return $value ?: $this->name;
    }

    public function userJabatanUnits()
    {
        return $this->hasMany(UserJabatanUnit::class, 'jabatan_id', 'id');
    }
}

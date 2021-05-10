<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AppReleases extends Model
{
    use HasFactory;

    protected $table = "app_releases";

    public function apps()
    {
        return $this->belongsTo(Apps::class, 'app_id', 'unique_id')->orderBy('id', 'DESC');
    }

    public function downloads()
    {
        return $this->hasMany(Downloads::class, 'app_release_id', 'unique_id');
    }

    // public function appReleases()
    // {
    //     return $this->hasMany(Downloads::class, 'app_id', 'app_id');
    // }

}

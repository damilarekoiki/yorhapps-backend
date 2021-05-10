<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Apps extends Model
{
    use HasFactory;

    protected $table = "apps";

    public function appReleases()
    {
        return $this->hasMany(AppReleases::class, 'app_id', 'unique_id')->orderBy('id', 'DESC');
    }

    public function downloads()
    {
        return $this->hasMany(Downloads::class, 'app_id', 'unique_id');
    }

}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Role extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'display_name'];

    // یک Role میتواند چندین Permission داشته باشد
    public function permissions()
    {
        return $this->belongsToMany(Permission::class, 'role_has_permissions', 'role_id', 'permission_id');
    }

    // یک Role میتواند به چندین User اختصاص داده شود (البته در این سیستم، User فقط یک Role دارد)
    public function users()
    {
        return $this->hasMany(User::class);
    }
}

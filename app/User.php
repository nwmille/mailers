<?php

namespace App;

use Illuminate\Notifications\Notifiable;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
    use Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name', 'email', 'password',
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password', 'remember_token',
    ];

    public function roles()
    {
        return $this->belongsToMany(Role::class);
    }

    public function giveRole(Role $role)
    {
        return $this->roles()->attach($role);
    }

    public function removeRole(Role $role)
    {
        return $this->roles()->detach($role);
    }

    public function hasRole($role)
    {
        return null !== $this->roles()->where(‘name’, $role)->first();
    }

    public function showUsers()
    {
        $all_users = User::all();


    }
}

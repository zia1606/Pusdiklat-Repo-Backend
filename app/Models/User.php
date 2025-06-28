<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasFactory, Notifiable, HasApiTokens;

    protected $fillable = [
        'name',
        'email',
        'password',
        'google_id',
        'avatar',
        'role_id'
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    // Relationship dengan Role
    public function role()
    {
        return $this->belongsTo(Role::class);
    }

    // Helper methods untuk check role
    public function isAdmin()
    {
        return $this->role && $this->role->name === 'admin';
    }

    public function isUser()
    {
        return $this->role && $this->role->name === 'user';
    }

    public function hasRole($roleName)
    {
        return $this->role && $this->role->name === $roleName;
    }
}
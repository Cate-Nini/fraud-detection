<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $primaryKey = 'id';

    protected $fillable = [
        'role_id',
        'status_id',
        'name',
        'email',
        'password',
        'phone',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    // Relationships
    public function role()
    {
        return $this->belongsTo(Role::class);
    }

    public function status()
    {
        return $this->belongsTo(Status::class);
    }

    public function accounts()
    {
        return $this->hasMany(Account::class);
    }

    public function auditLogs()
    {
        return $this->hasMany(AuditLog::class, 'admin_id');
    }

    // Helper methods
    public function isAdmin()
    {
        return $this->role->role_name === 'Admin';
    }

    public function isCustomer()
    {
        return $this->role->role_name === 'Customer';
    }
}
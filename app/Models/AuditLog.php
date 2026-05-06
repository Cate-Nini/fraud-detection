<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AuditLog extends Model
{
    protected $table = 'audit_logs';

    protected $fillable = [
        'admin_id',
        'action',
        'timestamp',
    ];

    public function admin()
    {
        return $this->belongsTo(User::class, 'admin_id');
    }
}
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    protected $fillable = [
        'account_id',
        'status_id',
        'type',
        'amount',
        'description',
        'date',
    ];

    public function account()
    {
        return $this->belongsTo(Account::class);
    }

    public function status()
    {
        return $this->belongsTo(Status::class);
    }

    public function fraudAnalysis()
    {
        return $this->hasOne(FraudAnalysis::class);
    }
}
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FraudAnalysis extends Model
{
    protected $table = 'fraud_analysis';

    protected $fillable = [
        'transaction_id',
        'prediction',
        'anomaly_score',
    ];

    public function transaction()
    {
        return $this->belongsTo(Transaction::class);
    }
}
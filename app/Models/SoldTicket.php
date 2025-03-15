<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SoldTicket extends Model
{
    use HasFactory;

    protected $fillable = [
        'id', // Foreign key
        'invoiceId',
        'event_id',     // Make sure to include this
        'customerDisplayName',
        'lowSeat',
        'highSeat',
        'section',
        'cost',
        'total',
        'profit',
        'roi',
        'invoiceDate',
    ];

    public function inventory()
    {
        return $this->belongsTo(Inventory::class, 'event_id');
    }

}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Inventory extends Model
{
    use HasFactory;
    protected $fillable = [
        'event_id', 
        'name', 
        'date', 
        'venue',
        'avg_profit_1d',
        'avg_profit_3d',
        'avg_profit_7d',
        'avg_profit_30d', 
        'sold', 
        'qty', 
        'unit_cost',
        'profit_margin',
        'stubhub_url',
        'vivid_url'
    ];

    public function soldTickets()
    {
        return $this->hasMany(SoldTicket::class, 'event_id');
    }


}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SoldInventoryTotal extends Model
{
    use HasFactory;
    protected $fillable = [
        'period', 
        'invoice_date_from', 
        'invoice_date_to', 
        'total_profit',
        'total_profit_margin',
        'total_qty'
    ];
}

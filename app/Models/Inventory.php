<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Inventory extends Model
{
    use HasFactory;
    protected $fillable = ['event_id', 'name', 'date', 'venue', 'sold', 'qty', 'profit_margin','stubhub_url','vivid_url'];
}

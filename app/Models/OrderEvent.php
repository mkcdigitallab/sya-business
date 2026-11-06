<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrderEvent extends Model
{
    public $timestamps = false;

    protected $fillable = ['order_id', 'actor', 'message', 'visible_to_client'];

    protected function casts(): array
    {
        return ['visible_to_client' => 'boolean', 'created_at' => 'datetime'];
    }
}

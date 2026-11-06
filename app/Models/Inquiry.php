<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Inquiry extends Model
{
    protected $fillable = ['reference', 'type', 'name', 'phone', 'email', 'service', 'message', 'status', 'staff_notes'];
}

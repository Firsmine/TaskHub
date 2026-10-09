<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class projects extends Model
{
    use App\Models\HasFactory;
    protected $fillable = [
        'user_id',
        'name',
        'description',
        'deadline'
    ];
}

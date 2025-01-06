<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CodeVerification extends Model
{
    use HasFactory;

    protected $fillable = [
        'code', 'status', 'user_id'
    ];

    public static function generate(){
        return rand(000000, 999999);
    }
}

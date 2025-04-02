<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Historique extends Model
{
    use HasFactory;
    use SoftDeletes;
    protected $fillable = ['user_id', 'libelle', 'description', 'date', 'montant', 'type'];

    public function user(){
        return $this->belongsTo(User::class);
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Produto extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'produtos';
    protected $guarded = [];

    public function transacoes()
    {
        return $this->belongsToMany(Transacao::class);
    }
}

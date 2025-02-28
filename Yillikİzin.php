<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Yillikİzin extends Model
{
    protected $table = 'yillik_izinler';

    protected $fillable = ['personel_id', 'yil', 'gun_sayisi'];

    public function personel()
    {
        return $this->belongsTo(Personel::class, 'personel_id', 'id');
    }
}

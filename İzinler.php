<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class İzinler extends Model
{
    protected $table = 'izinler';

    protected $fillable = ['personel_id', 'baslangic_tarihi', 'bitis_tarihi', 'gun_sayisi', 'yil', 'aciklama', 'toplam_izin', 'kullanilan_izin', 'kalan_izin'];


    protected $dates = [
        'baslangic_tarihi',
        'bitis_tarihi'
    ];

    public function personel()
    {
        return $this->belongsTo(Personel::class, 'personel_id', 'id');
    }
}

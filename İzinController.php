<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;
use App\Helpers\LogHelper;
use App\Models\İzinler;
use Illuminate\Http\Request;
use App\Models\Personel;
use App\Models\Yillikİzin;
use Illuminate\Support\Facades\DB;
use App\Services\IzinService;

class İzinController extends Controller
{
    protected $izinService;

    public function __construct(IzinService $izinService)
    {
        $this->izinService = $izinService;
    }

    public function index($personelId = null)
    {
        // Eğer personel ID'si verilmişse, sadece o personeli getir
        if ($personelId) {
            $personeller = Personel::where('id', $personelId)->get();
        } else {
            // Eğer personel ID'si verilmemişse, tüm personelleri getir
            $personeller = Personel::all();
        }

        // Personellerin izin bilgilerini döngüye alarak işle
        foreach ($personeller as $personel) {
            // Her yıl için izin özetini al
            $personel->izinler = DB::table('yillik_izinler as yi')
                ->select(
                    'yi.yil',
                    'yi.personel_id',
                    DB::raw('SUM(yi.gun_sayisi) as toplam_izin'),
                    DB::raw('COALESCE((
                        SELECT SUM(i.gun_sayisi)
                        FROM izinler i
                        WHERE i.personel_id = yi.personel_id
                        AND i.yil = yi.yil
                    ), 0) as kullanilan_izin')
                )
                ->where('yi.personel_id', $personel->id)
                ->groupBy('yi.yil', 'yi.personel_id')
                ->orderBy('yi.yil', 'desc')
                ->get()
                ->map(function($izin) {
                    // Her yıl için kalan izni hesapla
                    $izin->kalan_izin = max($izin->toplam_izin - $izin->kullanilan_izin, 0);
                    return $izin;
                });

            // Genel toplam izin hakkı
            $personel->toplam_izin = DB::table('yillik_izinler')
                ->where('personel_id', $personel->id)
                ->sum('gun_sayisi');

            // Genel kullanılan izin günleri
            $personel->kullanilan_izin = DB::table('izinler')
                ->where('personel_id', $personel->id)
                ->sum('gun_sayisi');

            // Genel kalan izin
            $personel->kalan_izin = max($personel->toplam_izin - $personel->kullanilan_izin, 0);

            // İzin durumu progress
            $personel->progress = $personel->toplam_izin > 0
                ? ($personel->kullanilan_izin / $personel->toplam_izin) * 100
                : 0;

            // Kullanılan izinlerin detayları
            $personel->kullanilan_izinler = DB::table('izinler')
                ->where('personel_id', $personel->id)
                ->select(
                    'yil',
                    'gun_sayisi',
                    'baslangic_tarihi',
                    'bitis_tarihi',
                    'aciklama'
                )
                ->orderBy('baslangic_tarihi', 'desc')
                ->get();
        }

        // View'a personel bilgilerini gönder
        return view('backend.izinler.index', compact('personeller'));
    }

    public function izinler()
    {
        $izinler = İzinler::with('personel')->get(); // Personel bilgilerini de çekiyoruz
        return view('backend.izinler.izin', compact('izinler'));
    }

    public function getIzin($id)
    {
        $izin = İzinler::find($id);
        return response()->json($izin);
    }

    public function izin_ekle(Request $request)
    {
        $personeller = Personel::all();
        $filteredIzinler = $this->getFilteredIzinler($personeller);
        return view('backend.izinler.create', compact('personeller', 'filteredIzinler'));
    }

    private function getFilteredIzinler($personeller)
    {
        $filteredIzinler = [];
        foreach ($personeller as $personel) {
            $izinler = Yillikİzin::where('personel_id', $personel->id)->get();
            foreach ($izinler as $izin) {
                $kalanIzin = $this->izinService->getKalanIzin($personel->id, $izin->yil);
                if ($kalanIzin > 0) {
                    $filteredIzinler[] = $izin;
                }
            }
        }
        return $filteredIzinler;
    }

    public function yil_kaydet(Request $request)
    {
        $request->validate([
            'personel_id' => 'required',
            'yil'         => 'required',
            'gun_sayisi'  => 'required|integer|min:1'
        ]);

        // Aynı personelin, aynı yıl için zaten kaydı var mı kontrol et
        $existingIzin = Yillikİzin::where('personel_id', $request->personel_id)
            ->where('yil', $request->yil)
            ->first();

        if ($existingIzin) {
            return back()->with('error', 'Bu personelin bu yıl için zaten bir kaydı var!');
        }

        // Yeni izin kaydını oluştur
        Yillikİzin::create([
            'personel_id' => $request->personel_id,
            'yil'         => $request->yil,
            'gun_sayisi'  => $request->gun_sayisi
        ]);

        return back()->with('success', 'İzin başarıyla eklendi.');
    }

    public function yil_guncelle(Request $request)
    {
        $request->validate([
            'personel_id' => 'required',
            'yil'         => 'required',
            'gun_sayisi'  => 'required|integer|min:1'
        ]);

        // Güncellenecek kaydı bul
        $izin = Yillikİzin::where('personel_id', $request->personel_id)
            ->where('yil', $request->yil)
            ->first();

        // Eğer kayıt yoksa hata döndür
        if (!$izin) {
            return back()->with('error', 'Bu personelin bu yıl için bir kaydı bulunamadı!');
        }

        // Güncellemeyi gerçekleştir
        $izin->update([
            'gun_sayisi' => $request->gun_sayisi
        ]);

        return back()->with('success', 'İzin başarıyla güncellendi.');
    }

    public function yil_sil(Request $request)
    {
        $request->validate([
            'personel_id' => 'required',
            'yil'         => 'required'
        ]);

        // Silinecek kaydı bul
        $izin = Yillikİzin::where('personel_id', $request->personel_id)
            ->where('yil', $request->yil)
            ->first();

        // Eğer kayıt bulunamazsa hata döndür
        if (!$izin) {
            return back()->with('error', 'Bu personelin bu yıl için bir kaydı bulunamadı!');
        }

        // Kaydı sil
        $izin->delete();

        return back()->with('success', 'İzin başarıyla silindi.');
    }

    public function izin_update(Request $request)
    {
        $izin = İzinler::find($request->izin_id);

        if ($izin) {
            $izin->yil = $request->yil;
            $izin->gun_sayisi = $request->gun_sayisi;
            $izin->baslangic_tarihi = $request->baslangic_tarihi;
            $izin->bitis_tarihi = $request->bitis_tarihi;
            $izin->aciklama = $request->aciklama;
            $izin->save();

            return redirect()->back()->with('success', 'İzin başarıyla güncellendi.');
        }

        return redirect()->back()->with('error', 'İzin  güncelleme başarısız.');
    }

    public function destroy($id)
    {
        $izin = İzinler::find($id);

        if ($izin) {
            $izin->delete();
            return redirect()->back()->with('success', 'İzin başarıyla silindi!');
        }

        return redirect()->back()->with('error', 'İzin bulunamadı!');
    }

    public function izinYillari(Request $request)
    {
        $personel_id = $request->input('personel_id');

        if (!$personel_id) {
            return response()->json([], 400); // Personel ID boşsa hata döndür
        }

        // Seçilen personelin izin yıllarını çek
        $yillar = Yillikİzin::where('personel_id', $personel_id)
            ->distinct() // Aynı yıl tekrar etmesin
            ->pluck('yil');

        return response()->json($yillar);
    }

    public function izin_kaydet(Request $request)
    {
        $validated = $request->validate([
            'personel_id'      => 'required|integer|exists:yillik_izinler,personel_id',
            'baslangic_tarihi' => 'required|date',
            'bitis_tarihi'     => 'required|date|after_or_equal:baslangic_tarihi',
            'gun_sayisi'       => 'required|integer|min:1',
            'aciklama'         => 'required|string',
            'yil'              => 'required|integer'
        ]);

        if ($this->izinService->getKalanIzin($validated['personel_id'], $validated['yil']) < $validated['gun_sayisi']) {
            return redirect()->back()->with('error', 'Seçilen yıl için izin hakkı bulunmuyor!');
        }

        $izin = $this->izinService->izinKullan(
            $validated['personel_id'],
            $validated['baslangic_tarihi'],
            $validated['bitis_tarihi'],
            $validated['gun_sayisi'],
            $validated['aciklama'],
            $validated['yil']
        );

        return redirect()->back()->with('success', 'İzin başarıyla eklendi.');
    }

    public function izinKalan(Request $request)
    {
        $request->validate([
            'personel_id' => 'required|integer',
            'yil'         => 'required|integer'
        ]);

        $personelID = $request->input('personel_id');
        $yil = $request->input('yil');

        // 🔹 1. Seçilen yıldaki toplam izin hakkını al
        $toplamIzin = DB::table('yillik_izinler')
            ->where('personel_id', $personelID)
            ->where('yil', $yil)
            ->value('gun_sayisi');

        // 🔹 2. Eğer izin hakkı yoksa hata ver
        if (is_null($toplamIzin)) {
            return redirect()->back()->with('error', 'Seçilen yıl için izin hakkı bulunmuyor!');
        }

        // 🔹 3. Seçilen yılda kullanılan toplam izin günlerini al
        $kullanilanIzin = DB::table('izinler')
            ->where('personel_id', $personelID)
            ->where('yil', $yil)
            ->sum('gun_sayisi');

        // 🔹 4. Kalan izin gününü hesapla
        $kalanIzin = max(0, $toplamIzin - $kullanilanIzin);

        return response()->json([
            'toplam_izin'     => $toplamIzin,
            'kullanilan_izin' => $kullanilanIzin,
            'kalan_izin'      => $kalanIzin
        ]);
    }

    // Seçilen yıllara göre personelin toplam izni kalan izni
    public function izinBilgileri(Request $request)
    {
        $personelID = $request->personel_id;
        $yil = $request->yil;

        // Seçilen yıl için toplam izin hakkı
        $toplamIzin = DB::table('yillik_izinler')
            ->where('personel_id', $personelID)
            ->where('yil', $yil)
            ->sum('gun_sayisi');

        // Seçilen yıl için kullanılan izin
        $kullanilanIzin = DB::table('izinler')
            ->where('personel_id', $personelID)
            ->where('yil', $yil)
            ->sum('gun_sayisi');

        // Kalan izin gün sayısı (0'dan küçük olmaması için max kullanıyoruz)
        $kalanIzin = max($toplamIzin - $kullanilanIzin, 0);

        return response()->json([
            'toplam_izin'     => $toplamIzin,
            'kullanilan_izin' => $kullanilanIzin,
            'kalan_izin'      => $kalanIzin
        ]);
    }
}
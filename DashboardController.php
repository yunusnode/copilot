<?php

namespace App\Http\Controllers;

use App\Models\Duyuru;
use App\Models\Event;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use App\Models\Department;

class DashboardController extends Controller
{
    public function index()
    {
        if (Auth::check()) {
            try {
                Carbon::setLocale('tr');
                $user = Auth::user();
                $userDepartmentId = $user->department_id;
                $today = Carbon::now()->startOfDay();

                // Yetkili departmanları belirle
                $authorizedDepartments = [$userDepartmentId];
                $authorizedEmails = [
                    $user->email => Department::pluck('id')->toArray()
                ];
                if (isset($authorizedEmails[$user->email])) {
                    $authorizedDepartments = array_merge($authorizedDepartments, $authorizedEmails[$user->email]);
                    $authorizedDepartments = array_unique($authorizedDepartments);
                }

                // Etkinlikleri getir
                $upcomingEvent = Event::where(function ($query) use ($user, $authorizedDepartments) {
                    // Kişisel etkinlikler
                    $query->where(function ($q) use ($user) {
                        $q->where('user_id', $user->id)
                            ->where('durum', 1);
                    })
                    // Yetkili olunan tüm departmanların etkinlikleri
                    ->orWhere(function ($q) use ($authorizedDepartments) {
                        $q->whereIn('department_id', $authorizedDepartments)
                            ->where('durum', 0);
                    });
                })
                ->where(function ($query) use ($today) {
                    $query->where(function ($q) use ($today) {
                        // Bugünkü etkinlikler
                        $q->whereDate('start', '<=', $today)
                            ->whereDate('end', '>=', $today);
                    })
                    ->orWhere(function ($q) use ($today) {
                        // Gelecek etkinlikler
                        $q->whereDate('start', '>=', $today);
                    })
                    ->orWhere(function ($q) use ($today) {
                        // Devam eden etkinlikler
                        $q->whereDate('start', '<', $today)
                            ->whereDate('end', '>', $today);
                    });
                })
                ->orderBy('start', 'asc')
                ->get();

                // Duyuruları getir
                $duyurular = Duyuru::where(function ($query) use ($authorizedDepartments) {
                    $query->where('type', 'general')
                        ->orWhere(function ($q) use ($authorizedDepartments) {
                            $q->where('type', 'department')
                                ->whereIn('department_id', $authorizedDepartments);
                        });
                })
                ->latest()
                ->get();

                // Dashboard için ek veriler
                $dashboardData = [
                    'currentUser' => [
                        'name' => $user->name,
                        'department' => $user->department_id,
                        'currentTime' => now()->format('Y-m-d H:i:s')
                    ],
                    'eventCounts' => [
                        'today' => $upcomingEvent->filter(function ($event) {
                            return Carbon::parse($event->start)->isToday() ||
                                (Carbon::parse($event->start)->isPast() &&
                                    Carbon::parse($event->end)->isFuture());
                        })->count(),
                        'upcoming' => $upcomingEvent->count()
                    ]
                ];
				
				// Haberleri önceden yükle
				$sonDakikaHaberleri = NewsController::fetchNews();
				$malatyaHaberleri = NewsController::fetchMalatyaNews();
				$dogansehirHaberleri = NewsController::fetchDoganSehirNews();
                return view('backend.pages.dashboard', compact(
                    'upcomingEvent',
                    'duyurular',
                    'dashboardData',
                    'sonDakikaHaberleri',
					'malatyaHaberleri',
					'dogansehirHaberleri'
                ));

                return view('backend.pages.dashboard', compact(
                    'upcomingEvent',
                    'duyurular',
                    'dashboardData'
                ));
            } catch (\Exception $e) {
                \Log::error('Dashboard Error: ' . $e->getMessage());
                return back()->with('error', 'Veriler yüklenirken bir hata oluştu.');
            }
        }

        return redirect()->route('login')->with('error', 'Bu alana erişiminiz yok!');
    }

    /**
     * Dashboard etkinlik bildirimlerini getir
     * 
     * @return \Illuminate\Http\JsonResponse
     */
    public function getEventNotifications()
    {
        try {
            Carbon::setLocale('tr');
            $user = Auth::user();
            $today = Carbon::now()->startOfDay();
            $endOfDay = Carbon::now()->endOfDay();

            // Sadece kullanıcının kendi departmanını yetkilendir
        $authorizedDepartments = [$user->department_id];

        // Eğer blade'de yetki kontrolü true ise tüm departmanları ekle
        if (auth()->user()->can('view-all-departments')) {
            $allDepartmentIds = Department::pluck('id')->toArray();
            $authorizedDepartments = array_merge($authorizedDepartments, $allDepartmentIds);
            $authorizedDepartments = array_unique($authorizedDepartments);
        }

            // Bugünkü etkinlikleri getir
            $todayEvents = Event::where(function ($query) use ($user, $authorizedDepartments) {
            // Kişisel etkinlikler
            $query->where(function ($q) use ($user) {
                $q->where('user_id', $user->id)
                    ->where('durum', 1);
            })
            // Yetkili olunan departmanların etkinlikleri
            ->orWhere(function ($q) use ($authorizedDepartments) {
                $q->whereIn('department_id', $authorizedDepartments)
                    ->where('durum', 0);
            });
        })
            ->where(function ($query) use ($today, $endOfDay) {
                // Bugün başlayan etkinlikler
                $query->whereDate('start', '=', $today)
                    // VEYA bugün biten etkinlikler
                    ->orWhereDate('end', '=', $today)
                    // VEYA devam eden etkinlikler
                    ->orWhere(function ($q) use ($today, $endOfDay) {
                        $q->where('start', '<=', $today)
                            ->where('end', '>=', $endOfDay);
                    });
            })
            ->orderBy('start', 'asc')
            ->get()
            ->map(function ($event) {
                $startDate = Carbon::parse($event->start);
                $endDate = Carbon::parse($event->end);
                $now = Carbon::now();

                // Etkinlik durumunu belirle
                $status = '';
                if ($startDate->isToday() && $startDate->isFuture()) {
                    $status = 'upcoming';
                } elseif ($startDate->isPast() && $endDate->isFuture()) {
                    $status = 'ongoing';
                } elseif ($endDate->isToday()) {
                    $status = 'ending';
                }

                return [
                    'id' => $event->id,
                    'title' => $event->title,
                    'start' => $startDate->translatedFormat('H:i'),
                    'end' => $endDate->translatedFormat('H:i'),
                    'type' => $event->durum == 1 ? 'personal' : 'unit',
                    'status' => $status,
                    'message' => $this->getEventMessage($status, $event->title, $startDate),
                    'remaining' => $this->getTimeRemaining($status, $startDate, $endDate)
                ];
            });

            // Bildirim verilerini hazırla
            $notificationData = [
                'success' => true,
                'total' => $todayEvents->count(),
                'events' => $todayEvents,
                'summary' => [
                    'personal' => $todayEvents->where('type', 'personal')->count(),
                    'unit' => $todayEvents->where('type', 'unit')->count(),
                ],
                'currentTime' => now()->format('Y-m-d H:i:s'),
                'nextCheck' => now()->addMinutes(15)->format('Y-m-d H:i:s')
            ];

            return response()->json($notificationData);
        } catch (\Exception $e) {
            \Log::error('Event Notification Error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Bildirimler alınırken bir hata oluştu.'
            ], 500);
        }
    }

    /**
     * Etkinlik mesajını oluştur
     */
    private function getEventMessage($status, $title, $startDate)
    {
        switch ($status) {
            case 'upcoming':
                $timeUntil = Carbon::now()->diffForHumans($startDate, ['parts' => 2, 'syntax' => CarbonInterface::DIFF_RELATIVE_TO_NOW]);
                return "{$title} etkinliği {$timeUntil} başlayacak";
            case 'ongoing':
                return "{$title} etkinliği devam ediyor";
            case 'ending':
                return "{$title} etkinliği bugün sona eriyor";
            default:
                return $title;
        }
    }

    /**
     * Kalan süreyi hesapla
     */
    private function getTimeRemaining($status, $startDate, $endDate)
    {
        $now = Carbon::now();

        switch ($status) {
            case 'upcoming':
                return $now->diffInMinutes($startDate);
            case 'ongoing':
                return $now->diffInMinutes($endDate);
            case 'ending':
                return $now->diffInMinutes($endDate);
            default:
                return 0;
        }
    }
}
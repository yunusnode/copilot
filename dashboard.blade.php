@extends('backend.base.app')

@section('title')
Panel
@endsection

@section('content')

@php
$currentUserEmail = Auth::user()->email;
$currentUserDepartment = Auth::user()->department_id;

// Departman ID'leri ve isimleri eşleştirmesi (view'in en başına ekleyin)
use App\Models\Department;
$departments = Department::pluck('name', 'id')->toArray();

// Yetkili kullanıcılar için departman erişim haritası
$authorizedEmails = [
   // 'yunus@admin.com' => [1], // İmar ve Şehircilik departmanına erişim
    'mustafa@yetkili.com' => [9], // İmar ve Şehircilik departmanına erişim
    'esat@yetkili.com' => [1,3,4,6,7,8], //
    //'emrah@mudur.com' => [2], // Yazı İşleri departmanına erişim
 
];

// Kullanıcının erişebileceği departman ID'lerini belirleme
$userDepartments = [$currentUserDepartment];

if (isset($authorizedEmails[$currentUserEmail])) {
    $userDepartments = array_merge($userDepartments, $authorizedEmails[$currentUserEmail]);
    $userDepartments = array_unique($userDepartments);
}

// Etkinlikleri filtrele
$filteredUpcomingEvent = collect();

foreach ($upcomingEvent as $event) {
    // Kişisel etkinlikleri her zaman göster
    if ($event->durum == 1 && $event->user_id == Auth::id()) {
        $filteredUpcomingEvent->push($event);
    }
    // Departman etkinliklerini kontrol et
    elseif ($event->durum == 0 && in_array($event->department_id, $userDepartments)) {
        $filteredUpcomingEvent->push($event);
    }
}

// Duyuruları filtrele
$filteredDuyurular = $duyurular->filter(function($duyuru) use ($userDepartments) {
    $currentDate = Carbon\Carbon::now();

    // Zamanı geçmiş duyuruları filtrele (created_at + belirli bir süre)
    $validUntil = $duyuru->created_at->addDays(3); // Örneğin 3 gün sonra geçersiz

    if ($validUntil->isPast()) {
        return false;
    }

    // Genel duyuruları her zaman göster
    if ($duyuru->type == 'general') {
        return true;
    }

    // Departman bazlı duyuruları yetki kontrolü ile göster
    return $duyuru->type == 'department' && in_array($duyuru->department_id, $userDepartments);
});

// Orijinal değişkenleri güncelle
$upcomingEvent = $filteredUpcomingEvent;
$duyurular = $filteredDuyurular;

// Dashboard data'yı güncelle
$dashboardData['eventCounts']['upcoming'] = $upcomingEvent->count();
@endphp



<link rel="stylesheet" href="{{ asset('backend/css/dashboard.css')}}">
<style>
/* Add pointer cursor to clickable notification items */
.notification-item[data-duyuru-id] {
    cursor: pointer;
}
</style>
    <div class="row">
        <!-- Announcements Card -->


        <!-- Ajanda Widget'ı -->
       <div class="col-md-4">
            <div class="card-anno">
                <div class="card-header-anno">
                    <h2>
                        Ajanda
                        @if($dashboardData['eventCounts']['upcoming'] > 0)
                        <span class="badge bg-primary ms-2">{{ $dashboardData['eventCounts']['upcoming'] }}</span>
                        @endif
                    </h2>
                </div>
                <div class="card-content-anno">
                    @forelse($upcomingEvent as $event)
                    @php
					$startDate = Carbon\Carbon::parse($event->start);
					$endDate = Carbon\Carbon::parse($event->end);
					$isToday = $startDate->isToday() ||
					($startDate->lessThanOrEqualTo(now()) &&
					$endDate->greaterThanOrEqualTo(now()));
					@endphp

                    <div class="announcement-item {{ $isToday ? 'today-event' : '' }}">
                        <div class="announcement-content">
                            <div class="d-flex justify-content-between align-items-start">
    <strong>{{ $event->title }}</strong>
    <span class="badge {{ $event->durum == 1 ? 'bg-success' : 'bg-purple' }} ms-2">
        @if($event->durum == 1)
            Kişisel
        @else
            {{ $departments[$event->department_id] ?? 'Birim' }}
        @endif
    </span>
</div>
                            <div class="event-dates">
                                @if($isToday)
                                <span class="badge bg-warning text-dark">
                                    @if($startDate->lessThan(now()) && $endDate->greaterThan(now()))
                                    Devam Ediyor
                                    @else
                                    Bugün
                                    @endif
                                </span>
                                @endif
                                <span class="announcement-date">
                                    {{ $startDate->translatedFormat('d F Y l') }}
                                    @if(!$startDate->isSameDay($endDate))
                                    <br>
                                    <small class="text-muted">
                                        Bitiş: {{ $endDate->translatedFormat('d F Y l') }}
                                    </small>
                                    @endif
                                </span>
                            </div>
                        </div>
                    </div>
                    @empty
                    <div class="announcement-item">
                        <div class="status-item">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                                <polyline points="22 4 12 14.01 9 11.01"></polyline>
                            </svg>
                            <span>Ajanda Bulunmamaktadır.</span>
                        </div>
                    </div>
                    @endforelse
                </div>
            </div>
        </div>


        <!-- Duyurular Card -->
        <div class="col-md-4">
    <div class="card-anno">
        <div class="card-header-anno">
            <h2>Duyurular</h2>
        </div>
        <div class="card-content-anno">
            @if ($duyurular->isEmpty())
            <div class="notification-item">
                <span class="notification-icon">🔔</span>
                <p>Hiç Duyuru Bulunmamaktadır.</p>
            </div>
            @else
            @foreach ($duyurular as $duyuru)
            <div id="duyuru-{{ $duyuru->id }}" class="notification-item" data-duyuru-id="{{ $duyuru->id }}" onclick="openModal({{ $duyuru->id }})">
                <span class="notification-icon">📢</span>
                <div>
                    <strong>{{ $duyuru->title }}</strong>
                    <p>{{ Str::limit($duyuru->content, 50) }}</p>
                    <span class="badge {{ $duyuru->type == 'general' ? 'badge-success' : 'badge-info' }}">
                        @if($duyuru->type == 'general')
                            Genel Duyuru
                        @else
                            {{ $departments[$duyuru->department_id] ?? 'Birim Duyurusu' }}
                        @endif
                    </span>
                    <small>{{ $duyuru->created_at->format('d-m-Y H:i') }}</small>
                </div>
            </div>
            @endforeach
            @endif
        </div>
    </div>
</div>

        {{-- Modalları ayrı bir bölümde topladık --}}
        @foreach ($duyurular as $duyuru)
        <div class="modal fade" id="modal-{{ $duyuru->id }}" tabindex="-1" aria-labelledby="modalLabel{{ $duyuru->id }}" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="modalLabel{{ $duyuru->id }}">{{ $duyuru->title }}</h5>
                        <button type="button" class="btn" data-bs-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                    </div>
                    <div class="modal-body">
                        <div class="duyuru-content">
                            <div class="duyuru-meta">
                                <span class="badge {{ $duyuru->type == 'general' ? 'badge-success' : 'badge-info' }}">
                                    {{ $duyuru->type == 'general' ? 'Genel Duyuru' : 'Birim Duyurusu' }}
                                </span>
                                <span class="duyuru-date">
                                    <i class="far fa-calendar-alt"></i>
                                    {{ $duyuru->created_at->format('d-m-Y H:i') }}
                                </span>
                            </div>
                            <div class="duyuru-text">
                                {{ $duyuru->content }}
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        
                    </div>
                </div>
            </div>
        </div>
        @endforeach


<!-- Haber Kart -->
<div class="col-md-4">
    <div class="card-anno">
        <div class="card-header-anno">
            <h2>Haberler</h2>
        </div>
        <div class="card-content-anno">
            <ul class="nav nav-tabs" id="birimTabs">
                <li class="nav-item">
                    <a class="nav-link active" id="islem1-tab" data-bs-toggle="tab" href="#son">Son Dakika</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" id="islem2-tab" data-bs-toggle="tab" href="#malatya">Malatya Haber</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" id="islem3-tab" data-bs-toggle="tab" href="#dogansehir">Doğanşehir</a>
                </li>
            </ul>
            <div class="tab-content mt-2">
                <div class="tab-pane fade show active" id="son">
                    @if(count($sonDakikaHaberleri) > 0)
                        <ul class="list-unstyled">
                            @foreach($sonDakikaHaberleri as $haber)
                                <li class="mb-3">
                                    <a href="{{ $haber['link'] }}" target="_blank" class="text-decoration-none">
                                        <h5 class="mb-2">{{ $haber['title'] }}</h5>
                                    </a>
                                    <p class="text-muted small">{{ $haber['description'] }}</p>
                                </li>
                                <hr>
                            @endforeach
                        </ul>
                    @else
                        <div class="alert alert-info">Haber bulunamadı.</div>
                    @endif
                </div>
                <div class="tab-pane fade" id="malatya">
                    @if(count($malatyaHaberleri) > 0)
                        <ul class="list-unstyled">
                            @foreach($malatyaHaberleri as $haber)
                                <li class="mb-3">
                                    <a href="{{ $haber['link'] }}" target="_blank" class="text-decoration-none">
                                        <h5 class="mb-2">{{ $haber['title'] }}</h5>
                                    </a>
                                    @if(isset($haber['image']))
                                        <img src="{{ $haber['image'] }}" alt="Haber Resmi" class="img-fluid mb-2" style="max-width: 200px;">
                                    @endif
                                    <p class="text-muted small">{{ $haber['description'] }}</p>
                                </li>
                                <hr>
                            @endforeach
                        </ul>
                    @else
                        <div class="alert alert-info">Haber bulunamadı.</div>
                    @endif
                </div>
                <div class="tab-pane fade" id="dogansehir">
                    @if(count($dogansehirHaberleri) > 0)
                        <ul class="list-unstyled">
                            @foreach($dogansehirHaberleri as $haber)
                                <li class="mb-3">
                                    <a href="{{ $haber['link'] }}" target="_blank" class="text-decoration-none">
                                        <h5 class="mb-2">{{ $haber['title'] }}</h5>
                                    </a>
                                    <p class="text-muted small">{{ $haber['description'] }}</p>
                                </li>
                                <hr>
                            @endforeach
                        </ul>
                    @else
                        <div class="alert alert-info">Haber bulunamadı.</div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

        <!-- Notifications Card 
        <div class="col-md-4">
            <div class="card-anno">
                <div class="card-header-anno">
                    <h2>Bildirimlerim ve Görevlerim</h2>
                </div>
                <div class="card-content-anno">
                    <div class="notification-item">
                        <span class="notification-icon">🔔</span>
                        <p>İmzalamış olduğunuz Encümen Gündemi konulu evrak Mustafa KAYA tarafından.</p>
                    </div>
                    <div class="notification-item">
                        <span class="notification-icon">🔔</span>
                        <p>İmzalamış olduğunuz Encümen Gündemi konulu evrak Memet BAYRAM tarafından imzalanmıştır.</p>
                    </div>
                    <div class="notification-item">
                        <span class="notification-icon">🔔</span>
                        <p>İmzalamış olduğunuz Kardeş Kent İlişkisi Kurulması konulu evrak Mustafa KAYA tarafından.</p>
                    </div>
                    <a href="#" class="show-all">Tümünü Göster</a>
                </div>
            </div>
        </div>-->
    </div>
    @section('js')
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

    <!-- Moment.js -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.29.1/moment.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.29.1/locale/tr.js"></script>

    <!-- Toastr -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        /* Bildirim kartının genel stilleri */
        #toast-container>.toast-info {
            background-color: #ffffff;
            /* Arka plan rengi */
            color:rgb(3, 3, 3);
            /* Yazı rengi */
            box-shadow: 0 0 15px rgba(0, 0, 0, 0.1);
            border-left: 4px solidrgb(3, 151, 251);
            /* Sol kenarlık rengi */
            opacity: 1;
        }
    </style>
    <script>
        $(document).ready(function() {
			moment.locale('tr'); // Türkçe dil desteği
            // Toastr ayarları
    toastr.options = {
        closeButton: true,         // Kapatma butonu göster
        progressBar: true,         // İlerleme çubuğu göster
        positionClass: "toast-top-right",
        timeOut: 3500,           // 3,5 saniye sonra kapanacak (10000 milisaniye)
        extendedTimeOut: 2000,    // Hover durumunda ek süre
        preventDuplicates: true,
        newestOnTop: true,
        showEasing: 'swing',
        hideEasing: 'linear',
        showMethod: 'fadeIn',
        hideMethod: 'fadeOut',
        closeOnHover: false       // Hover durumunda kapanmayı engelleme
    };

			
			
            function checkEventNotifications() {
                $.ajax({
                    url: '{{ route("dashboard.notifications.events") }}',
                    type: 'GET',
                    success: function(response) {
                        if (response.success && response.total > 0) {
                            // Bildirim içeriğini oluştur
                            let notificationHtml = `
                        <div class="today-events-notification">
                            <div class="notification-title mb-2">
                                <i class="fas fa-calendar-check me-2"></i>
                                <strong>Bugünkü Etkinlikleriniz (${response.total})</strong>
                            </div>
                            <div class="events-list">`;

                            // Etkinlikleri durumlarına göre grupla
                            const upcomingEvents = response.events.filter(e => e.status === 'upcoming');
                            const ongoingEvents = response.events.filter(e => e.status === 'ongoing');
                            const endingEvents = response.events.filter(e => e.status === 'ending');

                            // Yaklaşan etkinlikler
                            if (upcomingEvents.length > 0) {
                                notificationHtml += `
                            <div class="event-group mb-2">
                                <span class="badge bg-warning text-black mb-1">Yaklaşan</span>
                                ${upcomingEvents.map(event => `
                                    <div class="event-item">
                                        <strong>${event.title}</strong>
                                        <div class="small text-muted">
                                            ${event.message}
                                        </div>
                                    </div>
                                `).join('')}
                            </div>`;
                            }

                            // Devam eden etkinlikler
                            if (ongoingEvents.length > 0) {
                                notificationHtml += `
                            <div class="event-group mb-2">
                                <span class="badge bg-info mb-1">Devam Eden</span>
                                ${ongoingEvents.map(event => `
                                    <div class="event-item">
                                        <strong>${event.title}</strong>
                                        <div class="small text-muted">
                                            ${event.message}
                                        </div>
                                    </div>
                                `).join('')}
                            </div>`;
                            }

                            // Bugün biten etkinlikler
                            if (endingEvents.length > 0) {
                                notificationHtml += `
                            <div class="event-group">
                                <span class="badge bg-danger mb-1">Bugün Bitiyor</span>
                                ${endingEvents.map(event => `
                                    <div class="event-item">
                                        <strong>${event.title}</strong>
                                        <div class="small text-muted">
                                            ${event.message}
                                        </div>
                                    </div>
                                `).join('')}
                            </div>`;
                            }

                            notificationHtml += `
                            </div>
                            <hr>
                            <div class="text-center">
                                <a href="{{ route('fullcalender') }}" class="btn btn-sm btn-primary">
                                    <i class="fas fa-calendar-alt me-1"></i>Takvimi Görüntüle
                                </a>
                            </div>
                        </div>`;

                            // Bildirimi göster
                            toastr.info(notificationHtml, '', {
                                allowHtml: true,
                                onclick: function() {
                                    window.location.href = '{{ route("fullcalender") }}';
                                }
                            });
                        }
                    },
                    error: function(error) {
                        console.error('Bildirim hatası:', error);
                    }
                });
            }

            // Sayfa yüklendiğinde kontrol et
            checkEventNotifications();
			
			// Sayfa yenileme zamanlayıcısı
    function setupPageRefresh() {
        const refreshInterval = 5 * 60 * 1000; // 5 dakika
        let timeLeft = refreshInterval;
        
        // Kalan süreyi güncelle ve sayfayı yenile
        const countdown = setInterval(() => {
            timeLeft -= 1000;
            
            // Süre dolduğunda
            if (timeLeft <= 0) {
                clearInterval(countdown);
                window.location.reload();
            }
            
            // Debug için console'a yazdır (opsiyonel)
            // console.log(Sayfa yenilenmesine ${Math.ceil(timeLeft/1000)} saniye kaldı);
        }, 1000);

      
    }

    // Sayfa yenileme zamanlayıcısını başlat
    setupPageRefresh();

    // Sekme görünürlüğünü kontrol et
    document.addEventListener('visibilitychange', function() {
        if (!document.hidden) {
            // Sekme tekrar görünür olduğunda bildirimleri kontrol et
            checkEventNotifications();
        }
    });
            
        });


        // Modal açma fonksiyonu
        function openModal(id) {
            // Bootstrap Modal nesnesini oluştur
            const modalElement = document.getElementById(`modal-${id}`);
            const modal = new bootstrap.Modal(modalElement);
            // Modalı aç
            modal.show();
        }

        // Modal Kapatma Fonksiyonu
function closeModal() {
    // Tüm açık modalları bul
    const openModals = document.querySelectorAll('.modal.show');
    openModals.forEach(modalElement => {
        // Bootstrap 5 Modal nesnesini oluştur
        const modal = new bootstrap.Modal(modalElement);
        // Modalı kapat
        modal.hide();
    });
}

        // Sayfa yüklendiğinde çalışacak kodlar
document.addEventListener('DOMContentLoaded', function() {
    // Tüm duyurulara tıklama olayı ekle
    const duyurular = document.querySelectorAll('.notification-item');
    duyurular.forEach(duyuru => {
        duyuru.addEventListener('click', function() {
            const duyuruId = this.getAttribute('data-duyuru-id');
            openModal(duyuruId);
        });
    });

    // Modal kapanma olaylarını dinle
    document.querySelectorAll('.modal').forEach(modalElement => {
        modalElement.addEventListener('hidden.bs.modal', function() {
            // Modal kapandığında backdrop'ı ve modal-open sınıfını temizle
            document.body.classList.remove('modal-open');
            const backdrops = document.querySelectorAll('.modal-backdrop');
            backdrops.forEach(backdrop => backdrop.remove());
        });
    });
});
    </script>

    @endsection

@endsection
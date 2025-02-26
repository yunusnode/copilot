@php
$currentUserEmail = Auth::user()->email;
$currentUserDepartment = Auth::user()->department_id;

// Departman ID'leri ve isimleri eşleştirmesi
use App\Models\Department;
$departments = Department::pluck('name', 'id')->toArray();

// Yetkili kullanıcılar için departman erişim haritası
$authorizedEmails = [
	'esat@yetkili.com' => [1,3,4,6,7],
    'mustafa@yetkili.com' => [9],
];

// Kullanıcının erişebileceği departman ID'lerini belirleme
$userDepartments = [$currentUserDepartment];

if (isset($authorizedEmails[$currentUserEmail])) {
    $userDepartments = array_merge($userDepartments, $authorizedEmails[$currentUserEmail]);
    $userDepartments = array_unique($userDepartments);
}
@endphp

<script>
    // PHP'den gelen departments verisini JavaScript'te kullanmak için
    var departments = @json($departments);
</script>

<!DOCTYPE html>
<html lang="tr">

<head>
    <title>Etkinlik Takvimi</title>
    <meta charset="UTF-8">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">

    <!-- CSS Dosyaları -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/fullcalendar/3.9.0/fullcalendar.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css" />
    <link rel="stylesheet" href="{{asset('backend/css/calendar.css')}}">
    <link href="https://gitcdn.github.io/bootstrap-toggle/2.2.2/css/bootstrap-toggle.min.css" rel="stylesheet">
    <!-- Diğer meta tagler... -->
    <meta name="current-time" content="{{ now()->format('Y-m-d H:i:s') }}">
    <meta name="current-user" content="{{ Auth::user()->name }}">
    <meta name="user-id" content="{{ Auth::id() }}">
    <meta name="department-id" content="{{ Auth::user()->department_id }}">
</head>

<body>
    <div class="container mt-3">
        <div class="card shadow-sm">
            <div class="d-flex justify-content-between align-items-center w-100">
                <a href="{{route('dashboard')}}" class="btn btn-success">
                    <i class="fas fa-arrow-left me-2"></i>Panele Dön
                </a>

                <ul class="nav nav-tabs card-header-tabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="calendar-tab" data-bs-toggle="tab"
                            data-bs-target="#calendar-content" type="button" role="tab">
                            <i class="fas fa-calendar-alt me-2"></i>Takvim
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="agenda-tab" data-bs-toggle="tab"
                            data-bs-target="#agenda-content" type="button" role="tab">
                            <i class="fas fa-list me-2"></i>Ajandam
                        </button>
                    </li>
                </ul>
            </div>
            <div class="card-body p-0" style="max-height: 800px; overflow: hidden;">
                <div class="tab-content h-100">
                    <div class="tab-pane fade show active" id="calendar-content" role="tabpanel">
                        <div id="calendar"></div>
                    </div>
                    <div class="tab-pane fade h-100" id="agenda-content" role="tabpanel">
                        <div class="event-list" id="eventList"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Güncellenmiş Etkinlik Modalı -->
    <div class="modal fade" id="eventModal" tabindex="-1" aria-labelledby="eventModalLabel" role="dialog" aria-modal="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="eventModalLabel">Etkinlik</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Kapat"></button>
                </div>
                <div class="modal-body">
                    <form id="eventForm">
                        <input type="hidden" id="eventId">
                        <div class="mb-3">
                            <label for="eventTitle" class="form-label">Başlık</label>
                            <textarea class="form-control" id="eventTitle" required placeholder="Etkinlik başlığını girin" style="height: 150px;"></textarea>
                        </div>
                        <div class="mb-3">
                            <label for="eventStart" class="form-label">Başlangıç Tarihi</label>
                            <input type="date" class="form-control" id="eventStart" required>
                        </div>
                        <div class="mb-3">
                            <label for="eventEnd" class="form-label">Bitiş Tarihi</label>
                            <input type="date" class="form-control" id="eventEnd" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Etkinlik Tipi</label>
                            <div class="event-type-radio">
                                <div class="radio-group">
                                    <input type="radio" class="form-check-input" id="eventTypePersonal"
                                        name="durum" value="1" checked>
                                    <label class="form-check-label" for="eventTypePersonal">Kişisel</label>
                                </div>
                                <div class="radio-group">
                                    <input type="radio" class="form-check-input" id="eventTypeBirim"
                                        name="durum" value="0">
                                    <label class="form-check-label" for="eventTypeBirim">Birim</label>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-link" data-bs-dismiss="modal">İptal</button>
                    <button type="button" class="btn btn-danger" id="deleteEvent">Sil</button>
                    <button type="button" class="btn btn-primary" id="saveEvent">Kaydet</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Script Dosyaları -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.1.1/jquery.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.24.0/moment.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/fullcalendar/3.9.0/fullcalendar.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/fullcalendar/3.9.0/locale/tr.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>
    <script src="https://kit.fontawesome.com/your-font-awesome-kit.js"></script>
    <script src="https://gitcdn.github.io/bootstrap-toggle/2.2.2/js/bootstrap-toggle.min.js"></script>


    <style>
        /* Takvim Özel Stilleri */
        .fc-event {
            cursor: pointer;
            transition: background-color 0.3s ease;
        }

        .fc-event.personal-event {
            background-color: #10b981 !important; /* Kişisel etkinlik - Yeşil */
            border-color: #059669 !important;
        }

        .fc-event.unit-event {
            background-color: #7c3aed !important; /* Birim etkinliği - Mor */
            border-color: #6d28d9 !important;
        }

        .fc-event:hover {
            opacity: 0.9;
        }

        /* Mobil Uyumluluk */
        @media (max-width: 768px) {
            .fc-day,
            .fc-day-number {
                cursor: pointer;
                -webkit-tap-highlight-color: rgba(0, 0, 0, 0.1);
            }

            .fc-day:active,
            .fc-day-number:active {
                background-color: rgba(0, 0, 0, 0.1);
            }

            .modal-dialog {
                margin: 10px;
                width: calc(100% - 20px);
                max-width: none;
            }

            .fc-toolbar {
                display: flex;
                flex-direction: column;
                gap: 10px;
            }

            .fc-toolbar .fc-left,
            .fc-toolbar .fc-center,
            .fc-toolbar .fc-right {
                float: none;
                display: flex;
                justify-content: center;
                width: 100%;
            }
        }

        /* Bugünkü Etkinlikler Bildirimi Stili */
        .todays-events {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 1000;
            max-width: 300px;
        }

        .event-list-container .event-item {
            margin-bottom: 15px;
            padding: 15px;
            border: 1px solid #ddd;
            border-radius: 8px;
            background-color: #fff;
        }

        .event-list-container .personal-event {
            border-left: 4px solid #10b981;
        }

        .event-list-container .unit-event {
            border-left: 4px solid #7c3aed;
        }
    </style>

    <script>
        $(document).ready(function() {
            // Sabit değişkenler ve ayarlar
            const SITEURL = "{{ url('/user') }}";
            const CURRENT_DATE = moment("{{ now()->format('Y-m-d H:i:s') }}");
            const CURRENT_USER = "{{ Auth::user()->name }}";
            const CURRENT_USER_ID = "{{ Auth::id() }}";
            const CURRENT_DEPARTMENT_ID = "{{ Auth::user()->department_id }}";

            let calendar = null;
            let eventModal = new bootstrap.Modal(document.getElementById('eventModal'));

            // CSRF Token Ayarı
            $.ajaxSetup({
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                }
            });

            // Mobil cihaz kontrolü
            function isMobile() {
                return (window.innerWidth <= 768) ||
                    (/Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent));
            }

            // Toastr bildirim ayarları
            toastr.options = {
                closeButton: true,
                progressBar: true,
                positionClass: isMobile() ? "toast-bottom-center" : "toast-top-right",
                timeOut: 3000
            };

            // Modal formunu temizle
            function clearModalForm() {
                $('#eventId').val('');
                $('#eventTitle').val('');
                $('#eventForm')[0].reset();
                $('#deleteEvent').hide();
                $('#eventTypePersonal').prop('checked', true);
                $('#eventTypeBirim').prop('checked', false);
            }

            // Mesaj gösterme fonksiyonu
            function displayMessage(message, type = "success") {
                toastr[type](message, type === "success" ? "Başarılı" : "Hata");
            }

            

            // Etkinlik listesini güncelle
            function updateEventList() {
                const ITEMS_PER_PAGE = 5;

                $.ajax({
                    url: SITEURL + "/fullcalender",
                    type: "GET",
                    success: function(events) {
                        var eventList = $('#eventList');
                        eventList.empty();
						
						// Yetkili departmanları ve kullanıcı bilgilerini al
            var authorizedDepartments = @json($userDepartments);
            var currentUserId = {{ Auth::id() }};
            
            // Etkinlikleri filtrele
            var filteredEvents = events.filter(function(event) {
                var eventDepartmentId = parseInt(event.department_id);
                var eventDurum = parseInt(event.durum);
                var eventUserId = parseInt(event.user_id);

                if (eventDurum === 1) {
                    // Kişisel etkinlikler için kullanıcı kontrolü
                    return eventUserId === currentUserId;
                } else {
                    // Birim etkinlikleri için departman yetkisi kontrolü
                    return authorizedDepartments.includes(eventDepartmentId);
                }
            });

                        // Filtre bölümü
            var filterHtml = `
                <div class="event-filters mb-3 p-3 bg-light border rounded">
                    <div class="d-flex align-items-center justify-content-between">
                        <label class="form-label mb-0">Etkinlik Tipine Göre Filtrele:</label>
                        <select class="form-select ms-2" id="eventTypeFilter" style="width: auto;">
                            <option value="all">Tümü</option>
                            <option value="1">Kişisel</option>
                            <option value="0">Birim</option>
                        </select>
                    </div>
                </div>
            `;
            eventList.append(filterHtml);

                        var eventListContainer = $('<div class="event-list-container"></div>');
            eventList.append(eventListContainer);

                        // Filtrelenmiş etkinlikleri tarihe göre sırala
            var sortedEvents = filteredEvents.sort((a, b) => moment(a.start) - moment(b.start));
            var currentPage = 1;

                        function renderEventPage(page) {
                            const start = (page - 1) * ITEMS_PER_PAGE;
                            const end = start + ITEMS_PER_PAGE;
                            const pageEvents = sortedEvents.slice(start, end);

                            eventListContainer.empty();

                            if (sortedEvents.length === 0) {
                                eventListContainer.append('<div class="p-3 text-center text-muted">Henüz etkinlik eklenmemiş</div>');
                                return;
                            }

                            pageEvents.forEach(function(event) {
    var startDate = moment(event.start).format('DD.MM.YYYY');
    var endDate = event.end ?
        moment(event.end).subtract(0, 'days').format('DD.MM.YYYY') :
        startDate;

    // Departman adını al
    var departmentName = departments[event.department_id] || 'Bilinmeyen Birim';

    var eventHtml = `
        <div class="event-item ${event.durum === 1 ? 'personal-event' : 'unit-event'}" 
            data-event-id="${event.id}" 
            data-event-type="${event.durum}"
            data-department-id="${event.department_id}">
            <div class="event-header d-flex justify-content-between align-items-center">
                <div class="event-title">
                    ${event.title}
                    <span class="badge ${event.durum === 1 ? 'bg-success' : 'bg-primary'} ms-2">
                        ${event.durum === 1 ? 'Kişisel' : departmentName}
                    </span>
                </div>
            </div>
            <div class="event-date">
                ${startDate}${startDate !== endDate ? ' - ' + endDate : ''}
            </div>
            <div class="event-actions mt-2">
                <button class="btn btn-sm btn-outline-primary edit-event">
                    <i class="fas fa-edit"></i> Düzenle
                </button>
                <button class="btn btn-sm btn-outline-danger delete-list-event">
                    <i class="fas fa-trash"></i> Sil
                </button>
            </div>
        </div>
    `;
    eventListContainer.append(eventHtml);
});

                            // Sayfalama
                            const totalPages = Math.ceil(sortedEvents.length / ITEMS_PER_PAGE);
                            if (totalPages > 1) {
                                var paginationHtml = `
                            <nav aria-label="Sayfalama" class="mt-3">
                                <ul class="pagination justify-content-center">
                                    <li class="page-item ${page === 1 ? 'disabled' : ''}">
                                        <a class="page-link" href="#" data-page="${page - 1}">Önceki</a>
                                    </li>
                        `;

                                for (let i = 1; i <= totalPages; i++) {
                                    paginationHtml += `
                                <li class="page-item ${i === page ? 'active' : ''}">
                                    <a class="page-link" href="#" data-page="${i}">${i}</a>
                                </li>
                            `;
                                }

                                paginationHtml += `
                                    <li class="page-item ${page === totalPages ? 'disabled' : ''}">
                                        <a class="page-link" href="#" data-page="${page + 1}">Sonraki</a>
                                    </li>
                                </ul>
                            </nav>
                        `;
                                eventListContainer.append(paginationHtml);
                            }
                        }

                        // İlk sayfayı göster
                        renderEventPage(1);

                        // Sayfalama tıklama olayları
                        $(document).on('click', '.pagination .page-link', function(e) {
                            e.preventDefault();
                            const page = parseInt($(this).data('page'));
                            if (page && page !== currentPage) {
                                renderEventPage(page);
                                currentPage = page;
                            }
                        });

                        // Filtre değişikliği
                        $('#eventTypeFilter').on('change', function() {
    const selectedType = $(this).val();
    var authorizedDepartments = @json($userDepartments);
    var currentUserId = {{ Auth::id() }};

    // Önce yetki kontrolü ile filtreleme yap
    var authorizedEvents = events.filter(function(event) {
        var eventDepartmentId = parseInt(event.department_id);
        var eventDurum = parseInt(event.durum);
        var eventUserId = parseInt(event.user_id);

        if (eventDurum === 1) {
            return eventUserId === currentUserId;
        } else {
            return authorizedDepartments.includes(eventDepartmentId);
        }
    });

    // Sonra seçilen tipe göre filtrele
    if (selectedType === 'all') {
        sortedEvents = authorizedEvents.sort((a, b) => moment(a.start) - moment(b.start));
    } else {
        sortedEvents = authorizedEvents
            .filter(event => event.durum.toString() === selectedType)
            .sort((a, b) => moment(a.start) - moment(b.start));
    }

    currentPage = 1;
    renderEventPage(1);
});

                        // Düzenleme ve silme olayları
                        $(document).on('click', '.edit-event', function() {
                            const eventId = $(this).closest('.event-item').data('event-id');
                            const event = events.find(e => e.id === eventId);
                            if (event) {
                                $('#eventId').val(event.id);
                                $('#eventTitle').val(event.title);
                                $('#eventStart').val(moment(event.start).format('YYYY-MM-DD'));
                                $('#eventEnd').val(event.end ?
                                    moment(event.end).subtract(0, 'days').format('YYYY-MM-DD') :
                                    moment(event.start).format('YYYY-MM-DD'));

                                if (event.durum === 1) {
                                    $('#eventTypePersonal').prop('checked', true);
                                } else {
                                    $('#eventTypeBirim').prop('checked', true);
                                }

                                $('#deleteEvent').show();
                                eventModal.show();
                            }
                        });

                        $(document).on('click', '.delete-list-event', function() {
                            const eventId = $(this).closest('.event-item').data('event-id');
                            if (confirm("Etkinliği silmek istediğinizden emin misiniz?")) {
                                $.ajax({
                                    url: SITEURL + '/fullcalenderAjax',
                                    data: {
                                        id: eventId,
                                        type: 'delete'
                                    },
                                    type: "POST",
                                    success: function(response) {
                                        calendar.fullCalendar('refetchEvents');
                                        updateEventList();
                                        displayMessage("Etkinlik silindi");
                                    },
                                    error: function() {
                                        displayMessage("Silme işlemi başarısız", "error");
                                    }
                                });
                            }
                        });
                    }
                });
            }

            // Takvimi başlat
            function initializeCalendar() {
                calendar = $('#calendar').fullCalendar({
                    locale: 'tr',
					editable: true,
					displayEventTime: false,
					selectable: true,
				
        
			events: function(start, end, timezone, callback) {
        $.ajax({
            url: SITEURL + "/fullcalender",
            type: "GET",
            success: function(events) {
                var authorizedDepartments = @json($userDepartments);
                var currentUserId = {{ Auth::id() }};
                
                var filteredEvents = events.filter(function(event) {
                    var eventDepartmentId = parseInt(event.department_id);
                    var eventDurum = parseInt(event.durum);
                    var eventUserId = parseInt(event.user_id);

                    // Her etkinlik için bitiş tarihini düzelt
                    if (event.end) {
                        event.end = moment(event.end).add(1, 'days').format('YYYY-MM-DD'); // burası böyle olmalı
                    }

                    if (eventDurum === 1) {
                        return eventUserId === currentUserId;
                    } else {
                        return authorizedDepartments.includes(eventDepartmentId);
                    }
                });

                callback(filteredEvents);
            }
        });
    },
	
                    displayEventTime: false,
                    selectable: true,
                    selectHelper: true,
                    defaultDate: CURRENT_DATE,
					nextDayThreshold: '00:00:00',
                    height: isMobile() ? 'auto' : 800,
                    eventDurationEditable: true,
                    eventStartEditable: true,
                    droppable: true,
                    longPressDelay: 100,

                    header: {
                        left: 'prev,next today',
                        center: 'title',
                        right: 'month,agendaWeek,agendaDay'
                    },

                    views: {
                        month: {
                            buttonText: 'Ay',
                            titleFormat: 'MMMM YYYY'
                        },
                        agendaWeek: {
                            buttonText: 'Hafta',
                            titleFormat: 'D MMMM YYYY',
                            columnFormat: 'ddd D/M'
                        },
                        agendaDay: {
                            buttonText: 'Gün',
                            titleFormat: 'D MMMM YYYY'
                        }
                    },

                    selectConstraint: {
                        start: '00:00',
                        end: '24:00'
                    },

                    eventRender: function(event, element) {
						// Bitiş tarihini düzelt
        if (event.end) {
            event.end = moment(event.end).add(1, 'days');
        }
            var departmentName = departments[event.department_id] || 'Bilinmeyen Birim';
            var badgeClass = parseInt(event.durum) === 1 ? 'bg-success' : 'bg-purple';
            
            var eventContent = `
                <div class="fc-content">
                    <div class="d-flex justify-content-between align-items-start">
                        <span class="event-title">${event.title}</span>
                        <span class="badge ${badgeClass} event-badge">
                            ${parseInt(event.durum) === 1 ? 'Kişisel' : departmentName}
                        </span>
                    </div>
                </div>
            `;
            
            element.html(eventContent);

            if (parseInt(event.durum) === 1) {
                element.addClass('personal-event');
            } else {
                element.addClass('unit-event');
            }

        },

                    select: function(start, end, jsEvent, view) {
        clearModalForm();
        $('#eventStart').val(moment(start).format('YYYY-MM-DD'));
        $('#eventEnd').val(moment(end).subtract(1, 'days').format('YYYY-MM-DD'));
        $('#deleteEvent').hide();
        eventModal.show();
    },

                    dayClick: function(date, jsEvent, view) {
                        if (isMobile()) {
                            clearModalForm();
                            $('#eventStart').val(moment(date).format('YYYY-MM-DD'));
                            $('#eventEnd').val(moment(date).format('YYYY-MM-DD'));
                            $('#deleteEvent').hide();
                            eventModal.show();
                        }

                    },

                    eventClick: function(event) {
                        $('#eventId').val(event.id);
                        $('#eventTitle').val(event.title);
                        $('#eventStart').val(moment(event.start).format('YYYY-MM-DD'));
                        $('#eventEnd').val(event.end ?
                            moment(event.end).subtract(1, 'days').format('YYYY-MM-DD') :
                            moment(event.start).format('YYYY-MM-DD'));

                        if (event.durum === 1) {
                            $('#eventTypePersonal').prop('checked', true);
                        } else {
                            $('#eventTypeBirim').prop('checked', true);
                        }

                        $('#deleteEvent').show();
                        eventModal.show();
                    },

                    eventDrop: function(event, delta, revertFunc) {
                        var start = moment(event.start).format('YYYY-MM-DD');
                        var end = event.end ? moment(event.end).subtract(1, 'days').format('YYYY-MM-DD') : start;

                        $.ajax({
                            url: SITEURL + '/fullcalenderAjax',
                            data: {
                                title: event.title,
                                start: start,
                                end: end,
                                id: event.id,
                                durum: event.durum,
                                type: 'update'
                            },
                            type: "POST",
                            success: function(response) {
                                displayMessage("Etkinlik başarıyla taşındı");
                                updateEventList();
                            },
                            error: function() {
                                revertFunc();
                                displayMessage("Etkinlik taşınırken hata oluştu", "error");
                            }
                        });
                    }
                });
            }

            // Kaydet butonu işlemi
            $('#saveEvent').click(function() {
                var id = $('#eventId').val();
                var title = $('#eventTitle').val();
                var start = $('#eventStart').val();
                var end = $('#eventEnd').val();
                var durum = parseInt($('input[name="durum"]:checked').val());

                if (!title) {
                    displayMessage("Lütfen başlık giriniz", "error");
                    return;
                }

                if (!start || !end) {
                    displayMessage("Lütfen tarih seçiniz", "error");
                    return;
                }

                var type = id ? 'update' : 'add';
                var ajaxData = {
                    title: title,
                    start: start,
                    end: end,
                    durum: durum,
                    type: type
                };

                if (id) ajaxData.id = id;

                $.ajax({
                    url: SITEURL + '/fullcalenderAjax',
                    data: ajaxData,
                    type: "POST",
                    success: function(response) {
                        eventModal.hide();
                        calendar.fullCalendar('refetchEvents');
                        updateEventList();
                        displayMessage("Etkinlik " + (type === 'add' ? 'eklendi' : 'güncellendi'));
                    },
                    error: function() {
                        displayMessage("Bir hata oluştu", "error");
                    }
                });
            });

            // Sil butonu işlemi
            $('#deleteEvent').click(function() {
                var id = $('#eventId').val();

                if (confirm("Etkinliği silmek istediğinizden emin misiniz?")) {
                    $.ajax({
                        url: SITEURL + '/fullcalenderAjax',
                        data: {
                            id: id,
                            type: 'delete'
                        },
                        type: "POST",
                        success: function(response) {
                            eventModal.hide();
                            calendar.fullCalendar('refetchEvents');
                            updateEventList();
                            displayMessage("Etkinlik silindi");
                        },
                        error: function() {
                            displayMessage("Silme işlemi başarısız", "error");
                        }
                    });
                }
            });

            // Sayfa yüklendiğinde
            initializeCalendar();
            updateEventList();
            checkAndDisplayTodayEvents();

   

            // Pencere yeniden boyutlandığında takvimi güncelle
            $(window).resize(function() {
                calendar.fullCalendar('option', 'height', isMobile() ? 'auto' : 800);
            });
        });
    </script>
</body>

</html>
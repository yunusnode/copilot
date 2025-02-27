@extends('backend.base.app')

@section('title')
Günlük Yapılan İşler
@endsection

@section('content')
<div class="col md-12">
    <div class="card shadow-lg">
        <div class="card-header bg-info text-white">
            <h4 class="mb-0"><i class="fas fa-tasks"></i> Yapılacak İşler </h4>
        </div>
        <div class="card-body">
            @if ($errors->any())
            <div class="alert alert-danger">
                <ul>
                    @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
            @endif
            <!-- Form başlangıcı -->
            <form action="{{route('makine-kaydet')}}" method="POST" class="mb-3">
                @csrf
                <input type="hidden" name="department_id" value="{{ auth()->user()->department_id }}">
                <div class="row g-2">
                    <div class="col-md-4">
                        <input type="text" name="baslik" id="task-input" class="form-control" placeholder="Yapılacak iş...">
                    </div>
                    <div class="col-md-4">
                        <textarea name="icerik" class="form-control" placeholder="Açıklama..."></textarea>
                    </div>
                    <div class="col-md-3">
                        <input type="date" name="tarih" class="form-control" id="date-input">
                    </div>
                    <div class="col-md-1 text-center">
                        <button type="submit" class="btn btn-success">
                            Kaydet
                        </button>
                    </div>
                </div>
            </form>
            <!-- Form bitişi -->

        </div>


    </div>
    <div class="table-responsive">
        <table id="taskTable" class="table table-bordered text-center">
            <thead class="table-dark">
                <tr>
                    <th>Yapılan İş</th>
                    <th>Açıklama</th>
                    <th>Tarih</th>
                    <th>İşlemler</th>
                </tr>
            </thead>
            <tbody id="task-list">
                @foreach ($todos as $todo)
                <tr>
                    <td>{{$todo->baslik}}</td>
                    <td>{{$todo->icerik}}</td>
                    <td>{{$todo->tarih}}</td>
                    <td>
                        <button class="btn btn-info btn-sm show-details" data-id="{{ $todo->id }}">
                            <i class="fas fa-eye"></i> Detay
                        </button>

                        <button class="btn btn-info btn-sm edit-btn" data-id="{{ $todo->id }}" data-title="{{ $todo->baslik }}" data-description="{{ $todo->icerik }}" data-date="{{ $todo->tarih }}" data-durum="{{ $todo->durum }}">
                            Düzenle
                        </button>

                    </td>
                </tr>

                @endforeach
            </tbody>
        </table>
    </div>

</div>

<!-- İş Detay Modal -->
<div class="modal fade" id="taskModal" tabindex="-1" aria-labelledby="taskModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-info text-white">
                <h5 class="modal-title" id="taskModalLabel">İş Detayı</h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Kapat">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <p><strong>Başlık:</strong> <span id="taskTitle" class="font-weight-bold"></span></p>
                </div>
                <div class="mb-3">
                    <p><strong>Açıklama:</strong> <span id="taskDescription"></span></p>
                </div>
                <div class="mb-3">
                    <p><strong>Tarih:</strong> <span id="taskDate" class="text-muted"></span></p>
                </div>
                <div class="mb-3">
                    <p><strong>Durum: </strong>
                        <span id="taskDurum" class="badge"></span>
                    </p>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Kapat</button>
            </div>
        </div>
    </div>
</div>


<!-- Düzenleme İşlemi -->
<div class="modal fade" id="taskModal" tabindex="-1" aria-labelledby="taskModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-info text-white">
                <h5 class="modal-title" id="taskModalLabel">İş Detayı Düzenle</h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Kapat">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form id="editTaskForm">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="taskTitleInput"><strong>Başlık:</strong></label>
                        <input type="text" id="taskTitleInput" class="form-control">
                    </div>
                    <div class="mb-3">
                        <label for="taskDescriptionInput"><strong>Açıklama:</strong></label>
                        <textarea id="taskDescriptionInput" class="form-control"></textarea>
                    </div>
                    <div class="mb-3">
                        <label for="taskDateInput"><strong>Tarih:</strong></label>
                        <input type="date" id="taskDateInput" class="form-control">
                    </div>
                    <div class="mb-3">
                        <label for="taskDurumInput"><strong>Durum:</strong></label>
                        <select id="taskDurumInput" class="form-control">
                            <option value="0">Tamamlanmadı</option>
                            <option value="1">Tamamlandı</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Kapat</button>
                    <button type="submit" class="btn btn-primary">Düzenle</button>
                </div>
            </form>
        </div>
    </div>
</div>


@endsection

@section('js')
<!-- jQuery ve DataTables JS -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/dataTables.buttons.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/pdfmake.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/vfs_fonts.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.html5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.print.min.js"></script>

<script>
    $(document).ready(function() {
        if ($.fn.DataTable.isDataTable('#taskTable')) {
            $('#taskTable').DataTable().destroy();
        }

        $('#taskTable').DataTable({
            "responsive": true,
            "autoWidth": false,
            "pageLength": 10,
            "order": [
                [0, "asc"]
            ],

            "dom": 'Bfrtip',
            "buttons": [{
                    extend: 'excelHtml5',
                    text: '<i class="fas fa-file-excel"></i> Excel',
                    className: 'btn btn-success',
                    exportOptions: {
                        columns: ':not(:last-child)' // Son sütunu dışarı aktarmaz
                    }
                },
                {
                    extend: 'pdfHtml5',
                    text: '<i class="fas fa-file-pdf"></i> PDF',
                    className: 'btn btn-danger',
                    exportOptions: {
                        columns: ':not(:last-child)' // Son sütunu dışarı aktarmaz
                    }
                },
                {
                    extend: 'print',
                    text: '<i class="fas fa-print"></i> Yazdır',
                    className: 'btn btn-primary',
                    exportOptions: {
                        columns: ':not(:last-child)' // Son sütunu dışarı aktarmaz
                    }
                }
            ]
        });

        // Detay butonlarına tıklama işlemi
        $(".show-details").click(function() {
            var taskId = $(this).data("id");

            $.ajax({
                url: "/makine/todos/" + taskId,
                type: "GET",
                success: function(response) {
                    $("#taskTitle").text(response.baslik);
                    $("#taskDescription").text(response.icerik);
                    $("#taskDate").text(response.tarih);
                    $("#taskDurum").text(response.durum === 0 ? "Tamamlanmadı" : "Tamamlandı")
                        .removeClass("badge-success badge-danger")
                        .addClass(response.durum === 0 ? "badge-danger" : "badge-success");
                    // Modal'ı Bootstrap 5 ile açma
                    var modal = new bootstrap.Modal(document.getElementById('taskModal'));
                    modal.show();
                },
                error: function() {
                    alert("İş detayları getirilemedi.");
                }
            });
        });
    });

    // Butona tıklanınca modalı aç ve verileri yükle
    $(".edit-btn").click(function() {
        var taskId = $(this).data("id");
        var taskTitle = $(this).data("title");
        var taskDescription = $(this).data("description");
        var taskDate = $(this).data("date");
        var taskDurum = $(this).data("durum");

        // Modalı aç
        $("#taskModal").modal("show");

        // Verileri inputlara yükle
        $("#taskTitleInput").val(taskTitle);
        $("#taskDescriptionInput").val(taskDescription);
        $("#taskDateInput").val(taskDate);
        $("#taskDurumInput").val(taskDurum);
    });

    // Formu gönderdiğinde düzenleme işlemini yap
    $("#editTaskForm").submit(function(event) {
        event.preventDefault(); // Formun sayfayı yeniden yüklemesini engeller

        var taskId = $("#taskIdInput").val(); // ID'nin hidden inputu üzerinden alınabilir
        var taskTitle = $("#taskTitleInput").val();
        var taskDescription = $("#taskDescriptionInput").val();
        var taskDate = $("#taskDateInput").val();
        var taskDurum = $("#taskDurumInput").val();

        // AJAX ile verileri backend'e gönder
        $.ajax({
            url: '/task/update', // Backend'deki güncelleme route'u
            method: 'POST',
            data: {
                id: taskId,
                title: taskTitle,
                description: taskDescription,
                date: taskDate,
                durum: taskDurum,
                _token: '{{ csrf_token() }}' // CSRF token
            },
            success: function(response) {
                // Başarılı bir işlem sonrası yapılacaklar
                alert("Görev başarıyla güncellendi.");
                // Modalı kapat
                $("#taskModal").modal("hide");

                // Güncellenen verileri DOM'da güncelle
                // Örneğin:
                $("#taskTitle-" + taskId).text(taskTitle);
                $("#taskDescription-" + taskId).text(taskDescription);
                $("#taskDurum-" + taskId).text(taskDurum == "1" ? "Tamamlandı" : "Tamamlanmadı");
            },
            error: function() {
                alert("Bir hata oluştu.");
            }
        });
    });
</script>
@endsection
@section('css')
<link rel="stylesheet" href="{{ asset('backend/css/todo.css') }}">
<!-- DataTables CSS -->
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.2/css/buttons.dataTables.min.css">
<style>
    .table th,
    .table td {
        vertical-align: middle;
    }
</style>
@endsection
@extends('backend.base.app')

@section('title')
Tüm İzinler
@endsection

@section('content')
@if(Auth::check() && (Auth::user()->role === 'admin' || Auth::user()->role === 'yetkili') || Auth::user()->email === 'umit@mudur.com')
<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table id="example1" class="table table-bordered table-striped">
                <thead>
                    <tr>
                        <th>Ad Soyad</th>
                        <th>Başlangıç Tarihi</th>
                        <th>Bitiş Tarihi</th>
                        <th>Gün Sayısı</th>
                        <th>Yıl</th>
                        <th>Açıklama</th>
                        <th>İşlemler</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($izinler as $izin)
                    <tr>
                        <td>{{$izin->personel->ad_soyad ?? 'Bilinmiyor'}}</td>
                        <td>{{ \Carbon\Carbon::parse($izin->baslangic_tarihi)->format('d.m.Y') }}</td>
						<td>{{ \Carbon\Carbon::parse($izin->bitis_tarihi)->format('d.m.Y') }}</td>
                        <td>{{$izin->gun_sayisi}}</td>
                        <td>{{$izin->yil}}</td>
                        <td>{{$izin->aciklama}}</td>
                        <td>
                            <button class="btn btn-warning btn-sm" onclick="editIzin({{ $izin->id }})" data-bs-toggle="modal" data-bs-target="#editIzinModal">
                                Düzenle
                            </button>
                            @if(Auth::check() && Auth::user()->role === 'admin')
                            <form action="{{ route('izinler.destroy', $izin->id) }}" method="POST" style="display: inline;" onsubmit="return confirm('Bu izni silmek istediğinize emin misiniz?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-danger btn-sm delete-btn">Sil</button>
                            </form>
                            @endif

                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="text-center">Kayıt bulunamadı.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- İzin Düzenleme Modalı -->
<div class="modal fade" id="editIzinModal" tabindex="-1" aria-labelledby="editIzinModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editIzinModalLabel">İzin Düzenle</h5>
                <button type="button" class="btn" data-bs-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
            </div>
            <div class="modal-body">
                <form id="editIzinForm" action="{{ route('izinler.update') }}" method="POST">
                    @csrf
                    <input type="hidden" id="izin_id" name="izin_id">

                    <div class="mb-3">
                        <label for="yil" class="form-label">Yıl</label>
                        <input type="text" class="form-control" id="yil" name="yil" required>
                    </div>

                    <div class="mb-3">
                        <label for="gun_sayisi" class="form-label">Gün Sayısı</label>
                        <input type="number" class="form-control" id="gun_sayisi" name="gun_sayisi" required>
                    </div>

                    <div class="mb-3">
                        <label for="baslangic_tarihi" class="form-label">Başlangıç Tarihi</label>
                        <input type="date" class="form-control" id="baslangic_tarihi" name="baslangic_tarihi" required>
                    </div>

                    <div class="mb-3">
                        <label for="bitis_tarihi" class="form-label">Bitiş Tarihi</label>
                        <input type="date" class="form-control" id="bitis_tarihi" name="bitis_tarihi" required>
                    </div>

                    <div class="mb-3">
                        <label for="aciklama" class="form-label">Açıklama</label>
                        <textarea class="form-control" id="aciklama" name="aciklama" rows="3"></textarea>
                    </div>

                    <button type="submit" class="btn btn-success">Güncelle</button>
                </form>
            </div>
        </div>
    </div>
</div>


@endsection

@section('js')
<!-- jQuery'yi yükleyin (Önce jQuery yüklenmeli) -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

<!-- Bootstrap JS'yi yükleyin -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<script src="{{ asset('backend/plugins/datatables/jquery.dataTables.min.js')}}"></script>
<script src="{{ asset('backend/plugins/datatables-bs4/js/dataTables.bootstrap4.min.js')}}"></script>
<script src="{{ asset('backend/plugins/datatables-responsive/js/dataTables.responsive.min.js')}}"></script>
<script src="{{ asset('backend/plugins/datatables-buttons/js/dataTables.buttons.min.js')}}"></script>
<script src="{{ asset('backend/plugins/jszip/jszip.min.js')}}"></script>
<script src="{{ asset('backend/plugins/pdfmake/pdfmake.min.js')}}"></script>
<script src="{{ asset('backend/plugins/datatables-buttons/js/buttons.html5.min.js')}}"></script>
<script src="{{ asset('backend/plugins/datatables-buttons/js/buttons.print.min.js')}}"></script>

<script>
    function editIzin(izin_id) {
        console.log("editIzin fonksiyonu çalışıyor, ID:", izin_id); // Test için ekledik

        $.ajax({
            url: "/user/izinler/" + izin_id,
            type: "GET",
            success: function(response) {
                console.log("Gelen veri:", response); // Gelen veriyi kontrol edin

                $("#izin_id").val(response.id);
                $("#yil").val(response.yil);
                $("#gun_sayisi").val(response.gun_sayisi);
                $("#baslangic_tarihi").val(response.baslangic_tarihi);
                $("#bitis_tarihi").val(response.bitis_tarihi);
                $("#aciklama").val(response.aciklama);

                $("#editIzinModal").modal("show"); // Modal'ı elle açıyoruz
            },
            error: function(xhr, status, error) {
                console.error("Hata oluştu:", error);
            }
        });
    }


    $(document).ready(function() {
        var table = $("#example1").DataTable({
            dom: 'Bfrtip',
            buttons: [{
                    extend: 'copy',
                    text: 'Kopyala',
                    className: 'btn btn-secondary btn-sm'
                },
                {
                    extend: 'csv',
                    text: 'CSV',
                    className: 'btn btn-success btn-sm'
                },
                {
                    extend: 'excel',
                    text: 'Excel',
                    className: 'btn btn-primary btn-sm'
                },
                {
                    extend: 'pdf',
                    text: 'PDF',
                    className: 'btn btn-danger btn-sm'
                },
                {
                    extend: 'print',
                    text: 'Yazdır',
                    className: 'btn btn-info btn-sm'
                }
            ],
			language: {
                    decimal: ",",
                    thousands: ".",
                    search: "Ara:",
                    lengthMenu: "Sayfada _MENU_ kayıt göster",
                    zeroRecords: "Eşleşen kayıt bulunamadı",
                    info: "_TOTAL_ kayıttan _START_ - _END_ arası gösteriliyor",
                    infoEmpty: "Kayıt yok",
                    infoFiltered: "(_MAX_ kayıt içerisinden filtrelendi)",
                    paginate: {
                        first: "İlk",
                        last: "Son",
                        next: "İleri",
                        previous: "Geri"
                    }
                }
        });

		responsive: true
        $("#datatable-buttons").html(table.buttons().container());

        $("#prevPage, #prevPageBottom").on("click", function() {
            table.page("previous").draw("page");
        });

        $("#nextPage, #nextPageBottom").on("click", function() {
            table.page("next").draw("page");
        });
    });
</script>
@endsection

@endif
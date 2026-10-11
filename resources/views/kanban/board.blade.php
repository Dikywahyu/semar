@extends('layouts.app')

@section('content')
<div class="container-fluid">
    {{-- 1. BREADCRUMBS & BANNER ASLI (TIDAK DIGANTI) --}}
    <div class="card bg-info-subtle shadow-none position-relative overflow-hidden mb-4">
        <div class="card-body px-4 py-3">
            <div class="row align-items-center">
                <div class="col-9">
                    <h4 class="fw-semibold mb-8">To do List</h4>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a class="text-muted text-decoration-none" href="../dashboard">Home</a></li>
                            <li class="breadcrumb-item" aria-current="page">Kanban</li>
                        </ol>
                    </nav>
                </div>
                <div class="col-3 text-center">
                    <img src="{{ asset('dist/assets/images/breadcrumb/ChatBc.png') }}" alt="" class="img-fluid mb-n4" />
                </div>
            </div>
        </div>
    </div>

    {{-- INTERFACES INFORMASI PAPAN & INDIKATOR ROLE --}}
    <div class="d-flex align-items-center justify-content-between mb-4 flex-wrap gap-2">
        <div>
            <h5 class="fw-bold text-dark mb-1" id="board-title">Memuat Papan...</h5>
            <span class="badge bg-primary-subtle text-primary fw-semibold fs-2 px-2 py-1 rounded">
                <i class="ti ti-shield fs-3 align-middle"></i> Otoritas: {{ strtoupper($userRole) }}
            </span>
        </div>
        
        {{-- SWITCH BOARD PREMIUM & MANAJEMEN KOLABORASI --}}
        <div class="d-flex align-items-center gap-2 flex-wrap">
            <div class="dropdown">
                <button class="btn btn-white border border-gray-300 dropdown-toggle fw-semibold d-flex align-items-center gap-2 shadow-sm px-3" type="button" id="dropdownSwitchBoard" data-bs-toggle="dropdown" aria-expanded="false" style="background: #fff; min-width: 220px;">
                    <i class="ti ti-layout-board fs-5 text-primary"></i>
                    <span class="text-truncate flex-grow-1 text-start" id="active-board-name">Pilih Papan Kerja</span>
                </button>
                <ul class="dropdown-menu dropdown-menu-end shadow-sm border-0 animated-dropdown" aria-labelledby="dropdownSwitchBoard" style="min-width: 260px; max-height: 300px; overflow-y: auto;">
                    <li class="dropdown-header text-uppercase fs-2 fw-bold text-muted tracking-wider">Daftar Papan Anda</li>
                    @foreach($allBoards as $b)
                        <li>
                            <a class="dropdown-item d-flex align-items-center justify-content-between py-2 px-3 {{ $id == $b->id ? 'active bg-primary-subtle text-primary fw-bold' : '' }}" href="/kanban?board_id={{ $b->id }}">
                                <div class="d-flex align-items-center gap-2 text-truncate" style="max-width: 180px;">
                                    <i class="ti ti-subtask fs-4 text-muted"></i>
                                    <span class="text-truncate">{{ $b->name }}</span>
                                </div>
                                @if($b->user_id != Auth::id())
                                    <span class="badge bg-warning-subtle text-warning font-medium fs-1 py-1 px-2 rounded-pill">Shared</span>
                                @else
                                    <span class="badge bg-info-subtle text-info font-medium fs-1 py-1 px-2 rounded-pill">Owner</span>
                                @endif
                            </a>
                        </li>
                    @endforeach
                </ul>
            </div>

            <button class="btn btn-primary d-flex align-items-center gap-1 shadow-sm" id="btn-add-board-modal" title="Buat Papan Baru">
                <i class="ti ti-layout-grid-add fs-5"></i> <span class="d-none d-sm-inline">Papan Baru</span>
            </button>
            
            @if($userRole == 'owner')
                <button class="btn btn-success d-flex align-items-center gap-1 shadow-sm" id="btn-share-modal">
                    <i class="ti ti-users fs-5"></i> <span class="d-none d-sm-inline">Kolaborasi</span>
                </button>
                <button class="btn btn-danger d-flex align-items-center p-2 shadow-sm" id="btn-delete-board" title="Hapus Papan">
                    <i class="ti ti-trash fs-5"></i>
                </button>
            @endif
        </div>
    </div>

    {{-- 2. WADAH UTAMA KANBAN BOARD --}}
    <div class="parent-container" style="overflow-x: auto; white-space: nowrap; padding-bottom: 20px;">
        <div id="kanban-stages-container" class="row flex-nowrap g-3 m-0" style="min-height: 500px;">
            {{-- Data Kolom dan Kartu di-render via AJAX --}}
        </div>
    </div>
</div>

{{-- ========================================== --}}
{{--             MODAL COMPONENT SECTION        --}}
{{-- ========================================== --}}

{{-- MODAL 1: TAMBAH BOARD --}}
<div class="modal fade" id="addBoardModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-primary-subtle">
                <h5 class="modal-title fw-bold text-primary">Buat Papan Kerja Baru</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="form-add-board">
                @csrf
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-semibold text-dark">Nama Papan</label>
                        <input type="text" class="form-control" name="name" placeholder="Contoh: Modul KRS Mahasiswa" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold text-dark">Deskripsi Singkat</label>
                        <textarea class="form-control" name="description" rows="2" placeholder="Tuliskan tujuan tim di papan ini..."></textarea>
                    </div>
                </div>
                <div class="modal-footer border-top-0">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">Buat Papan</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- MODAL 2: TAMBAH TASK TUGAS --}}
<div class="modal fade" id="addItemModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-info-subtle">
                <h5 class="modal-title fw-bold text-info">Tambah Tugas Baru</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="form-add-task">
                @csrf
                <input type="hidden" name="stage_id" id="modal-stage-id">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Judul Tugas</label>
                        <input type="text" class="form-control" name="title" required placeholder="Bentuk perbaikan / fitur baru">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Deskripsi Detail Pekerjaan</label>
                        <textarea class="form-control" name="description" rows="3" placeholder="Tulis spesifikasi tugas..."></textarea>
                    </div>
                    <div class="row">
                        <div class="col-6 mb-3">
                            <label class="form-label fw-semibold">Prioritas</label>
                            <select class="form-select" name="priority">
                                <option value="low">Low</option>
                                <option value="medium" selected>Medium</option>
                                <option value="high">High</option>
                            </select>
                        </div>
                        <div class="col-6 mb-3">
                            <label class="form-label fw-semibold">Deadline</label>
                            <input type="date" class="form-control" name="due_date">
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-top-0">
                    <button type="submit" class="btn btn-info text-white">Simpan Tugas</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- MODAL 3: EDIT DATA TASK TUGAS --}}
<div class="modal fade" id="editItemModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-success-subtle">
                <h5 class="modal-title fw-bold text-success">Edit Detail Tugas</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="form-edit-task">
                @csrf
                @method('PUT')
                <div class="modal-body">
                    <input type="hidden" id="edit-task-id">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Judul Tugas</label>
                        <input id="edit-kanban-title" type="text" class="form-control" name="title" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Deskripsi Pekerjaan</label>
                        <textarea id="edit-kanban-text" class="form-control" name="description" rows="3"></textarea>
                    </div>
                    <div class="row">
                        <div class="col-6 mb-3">
                            <label class="form-label fw-semibold">Tingkat Prioritas</label>
                            <select class="form-select" name="priority" id="edit-kanban-priority">
                                <option value="low">Low</option>
                                <option value="medium">Medium</option>
                                <option value="high">High</option>
                            </select>
                        </div>
                        <div class="col-6 mb-3">
                            <label class="form-label fw-semibold">Batas Waktu (Deadline)</label>
                            <input type="date" class="form-control" name="due_date" id="edit-kanban-duedate">
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-top-0">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-success">Simpan Perubahan</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- MODAL 4: KOLABORASI / MANAGEMEN MEMBER --}}
<div class="modal fade" id="shareBoardModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-success-subtle">
                <h5 class="modal-title fw-bold text-success"><i class="ti ti-users align-middle me-1"></i> Kolaborasi Papan Kerja</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <h6 class="fw-semibold mb-2 text-dark fs-3">Undang Rekan Kolaborator</h6>
                <form id="form-share-board" class="row g-2 mb-4 align-items-end">
                    @csrf
                    <input type="hidden" name="board_id" value="{{ $id }}">
                    <div class="col-md-6">
                        <label class="form-label small fw-bold text-muted mb-1">Pilih Akun</label>
                        <select class="form-select" name="user_id" id="select-user-invite" required></select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small fw-bold text-muted mb-1">Otoritas Akses</label>
                        <select class="form-select" name="role">
                            <option value="member">Member (Bisa Edit & Geser)</option>
                            <option value="viewer">Viewer (Hanya Bisa Melihat)</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-success w-100 d-flex align-items-center justify-content-center gap-1" style="height: 38px;">
                            <i class="ti ti-user-plus fs-4"></i> Tambah
                        </button>
                    </div>
                </form>

                <hr class="border-light-subtle my-4">

                <h6 class="fw-semibold mb-2 text-dark fs-3">Daftar Kolaborator Aktif</h6>
                <div class="table-responsive border rounded-2" style="background: #fafafa;">
                    <table class="table table-sm align-middle mb-0 text-nowrap">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-3">Nama Pengguna</th>
                                <th>Otoritas (Role)</th>
                                <th class="text-end pe-3">Aksi Pemutusan</th>
                            </tr>
                        </thead>
                        <tbody id="list-members-table">
                            {{-- Diisi via AJAX --}}
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://code.jquery.com/ui/1.13.2/jquery-ui.min.js"></script> 
<script src="https://cdnjs.cloudflare.com/ajax/libs/jqueryui-touch-punch/0.2.3/jquery.ui.touch-punch.min.js"></script>
<script>
$(document).ready(function() {
    const boardId = "{{ $id }}";
    const userRole = "{{ $userRole }}"; 

    $.ajaxSetup({ headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') } });

    function loadBoard() {
        $.get(`/kanban/board-data/${boardId}`, function(res) {
            if(res.success) renderBoard(res.data);
        });
    }

    function renderBoard(board) {
        $('#board-title').text(board.name);
        $('#active-board-name').text(board.name);
        
        let html = '';
    
        // ANTISIPASI: Jika papan terlanjur dibuat tanpa ada data kolom/stages di database
        if (!board.stages || board.stages.length === 0) {
            $('#kanban-stages-container').html(`
                <div class="col-12 text-center py-5 w-100">
                    <div class="p-4 mx-auto rounded-3 bg-light border" style="max-width: 500px;">
                        <i class="ti ti-layout-sidebar fs-9 text-warning mb-2 d-block"></i>
                        <h6 class="fw-bold text-dark fs-4">Kolom Belum Terbentuk</h6>
                        <p class="text-muted small mb-3">Papan ini belum memiliki baris status (Todo, In Progress, Done).</p>
                        <small class="text-black-50 d-block border-top pt-2">
                            <strong>Solusi Cepat:</strong> Klik tombol <span class="badge bg-primary">Papan Baru</span> di kanan atas untuk membuat papan baru yang kolomnya langsung otomatis terbuat dengan sempurna.
                        </small>
                    </div>
                </div>
            `);
            return; // Hentikan fungsi agar tidak terjadi error pembacaan foreach
        }
        
        // Looping data kolom/stages jika tersedia
        board.stages.forEach(stage => {
            let addBtn = userRole !== 'viewer' ? `<button class="btn btn-sm btn-white text-primary border shadow-sm rounded-circle p-1 d-flex align-items-center justify-content-center btn-add-task" data-id="${stage.id}"><i class="ti ti-plus fs-4"></i></button>` : '';
            
            html += `
            <div class="col-12 col-md-4 px-2" style="min-width: 320px; max-width: 360px;">
                <div class="bg-light p-3 rounded-3 border-0 shadow-sm h-100 d-flex flex-column" style="background-color: #f6f9fc !important;">
                    <div class="d-flex justify-content-between mb-3 align-items-center border-bottom pb-2">
                        <h6 class="fw-bold mb-0 text-dark fs-4" style="border-left: 4px solid ${stage.color || '#0085db'}; padding-left: 8px;">${stage.name}</h6>
                        ${addBtn}
                    </div>
                    <div class="connect-sorting-content d-flex flex-column gap-2 flex-grow-1" data-stage-id="${stage.id}" style="min-height: 450px; max-height: 650px; overflow-y: auto;">
            `;
    
            if(stage.tasks && stage.tasks.length > 0) {
                stage.tasks.forEach(task => {
                    let badgeColor = task.priority === 'high' ? 'bg-danger' : (task.priority === 'medium' ? 'bg-warning text-dark' : 'bg-success');
                    let displayDate = task.due_date ? task.due_date : 'No Deadline';
                    
                    let actionButtons = '';
                    if(userRole !== 'viewer') {
                        actionButtons = `
                        <div class="d-flex gap-2">
                            <a class="text-muted btn-edit-task-trigger" data-id="${task.id}" data-title="${task.title}" data-desc="${task.description || ''}" data-priority="${task.priority}" data-date="${task.due_date || ''}" href="javascript:void(0);" title="Edit Tugas">
                                <i class="ti ti-pencil fs-4 text-info-hover text-info"></i>
                            </a>
                            <a class="text-muted btn-del-task" data-id="${task.id}" href="javascript:void(0);" title="Hapus Tugas">
                                <i class="ti ti-trash fs-4 text-danger-hover text-danger"></i>
                            </a>
                        </div>`;
                    }
    
                    html += `
                    <div class="card kanban-card-item mb-0 shadow-sm border rounded-2" data-id="${task.id}" id="task-${task.id}" style="background-color: #ffffff; cursor: ${userRole !== 'viewer' ? 'grab' : 'default'};">
                        <div class="card-body p-3">
                            <div class="d-flex justify-content-between align-items-start gap-2 mb-2">
                                <h6 class="fw-bold text-dark text-wrap mb-0" style="max-width: 80%; font-size:14px;">${task.title}</h6>
                                ${actionButtons}
                            </div>
                            <p class="small text-muted mb-2 text-wrap shadow-none lh-sm fs-2">${task.description || '<em class="text-black-50">Tidak ada deskripsi</em>'}</p>
                            <div class="d-flex justify-content-between align-items-center mt-2 pt-2 border-top border-light-subtle">
                                <span class="fs-1 text-muted d-flex align-items-center gap-1" style="font-size:11px;">
                                    <i class="ti ti-calendar fs-3"></i> ${displayDate}
                                </span>
                                <span class="badge ${badgeColor} fs-1 px-2 py-1 rounded" style="font-size:10px;">${task.priority.toUpperCase()}</span>
                            </div>
                        </div>
                    </div>`;
                });
            }
            html += `</div></div></div>`;
        });
        
        $('#kanban-stages-container').html(html);
    
        if(userRole !== 'viewer') {
            initSortable();
        }
    }

    function initSortable() {
        $(".connect-sorting-content").sortable({
            connectWith: ".connect-sorting-content",
            placeholder: "bg-primary-subtle border border-dashed border-primary rounded-2 style-placeholder",
            items: ".kanban-card-item",
            opacity: 0.85,
            cursor: "move",
            start: function(e, ui) { ui.placeholder.height(ui.item.height()); },
            update: function(event, ui) {
                if (this === ui.item.parent()[0]) {
                    let task_id = ui.item.data('id');
                    let target_stage_id = ui.item.parent().data('stage-id');
                    let ordered_ids = ui.item.parent().find('.kanban-card-item').map(function(){ return $(this).data('id'); }).get();
                    
                    $.post("{{ route('kanban.task.updatePosition') }}", { task_id, target_stage_id, ordered_ids });
                }
            }
        }).disableSelection();
    }

    $(document).on('click', '.btn-add-task', function() {
        let stageId = $(this).data('id');
        $('#modal-stage-id').val(stageId);
        $('#form-add-task')[0].reset();
        $('#addItemModal').modal('show');
    });

    $('#form-add-task').submit(function(e) {
        e.preventDefault();
        $.post("/kanban/task/store", $(this).serialize(), function(res) {
            if(res.success) {
                $('#addItemModal').modal('hide');
                loadBoard();
            }
        });
    });

    $(document).on('click', '.btn-edit-task-trigger', function(e) {
        e.preventDefault();
        let taskId = $(this).data('id');
        let title = $(this).data('title');
        let desc = $(this).data('desc');
        let priority = $(this).data('priority');
        let date = $(this).data('date');

        $('#edit-task-id').val(taskId);
        $('#edit-kanban-title').val(title);
        $('#edit-kanban-text').val(desc);
        $('#edit-kanban-priority').val(priority);
        $('#edit-kanban-duedate').val(date);

        $('#editItemModal').modal('show');
    });

    // SUBMIT FORM EDIT TASK 
    $('#form-edit-task').submit(function(e) {
        e.preventDefault();
        
        let taskId = $('#edit-task-id').val();
        
        // Menggunakan URLSearchParams agar data serialize aman dikirim via PUT
        let formData = $(this).serialize();

        $.ajax({
            url: `/kanban/task/update/${taskId}`,
            method: 'PUT', 
            data: formData, // Data string hasil serialize dialirkan ke PUT
            dataType: 'json',
            success: function(res) {
                if(res.success) {
                    $('#editItemModal').modal('hide');
                    loadBoard();
                    Swal.fire({ 
                        icon: 'success', 
                        title: 'Tugas diperbarui', 
                        timer: 1000, 
                        showConfirmButton: false 
                    });
                }
            },
            error: function(xhr) {
                // Log untuk mempermudah tracking jika masih ada kendala server
                console.error("Error Detail:", xhr.responseText);
                alert('Gagal memperbarui tugas. Silakan cek konsol F12.');
            }
        });
    });

    $('#btn-share-modal').click(function() {
        loadAvailableUsers();
        loadCurrentMembers();
        $('#shareBoardModal').modal('show');
    });

    function loadAvailableUsers() {
        $.get(`/kanban/board/${boardId}/users`, function(res) {
            let options = '<option value="" disabled selected>-- Pilih Akun Kolaborator --</option>';
            res.data.forEach(u => options += `<option value="${u.id}">${u.name} (${u.email})</option>`);
            $('#select-user-invite').html(options);
        });
    }

    function loadCurrentMembers() {
        $.get(`/kanban/board/${boardId}/members`, function(res) {
            let rows = '';
            if(res.data.length === 0) {
                rows = `<tr><td colspan="3" class="text-center py-3 text-muted">Belum ada rekan yang diundang ke papan ini.</td></tr>`;
            } else {
                res.data.forEach(m => {
                    rows += `
                    <tr>
                        <td class="ps-3 fw-bold text-dark">${m.name}<br><small class="text-muted font-normal">${m.email}</small></td>
                        <td>
                            <select class="form-select form-select-sm change-role fw-semibold" data-user-id="${m.id}" style="width: 140px;">
                                <option value="member" ${m.pivot.role == 'member' ? 'selected' : ''}>Member (Editor)</option>
                                <option value="viewer" ${m.pivot.role == 'viewer' ? 'selected' : ''}>Viewer (Read)</option>
                            </select>
                        </td>
                        <td class="text-end pe-3">
                            <button class="btn btn-sm btn-light-danger text-danger remove-member p-1 rounded" data-user-id="${m.id}" title="Cabut Akses">
                                <i class="ti ti-user-x fs-5"></i>
                            </button>
                        </td>
                    </tr>`;
                });
            }
            $('#list-members-table').html(rows);
        });
    }

    $(document).on('change', '.change-role', function() {
        let user_id = $(this).data('user-id');
        let role = $(this).val();
        $.post("{{ route('kanban.member.update') }}", { board_id: boardId, user_id, role }, function() {
            Swal.fire({ icon: 'success', title: 'Otoritas diperbarui', timer: 1000, showConfirmButton: false });
        });
    });

    $(document).on('click', '.remove-member', function() {
        let user_id = $(this).data('user-id');
        Swal.fire({ title: 'Keluarkan User?', text: "Akses kolaborator pada papan ini akan dicabut.", icon: 'warning', showCancelButton: true }).then(res => {
            if(res.isConfirmed) {
                $.ajax({
                    url: `/kanban/board/member/remove/${boardId}/${user_id}`,
                    method: 'DELETE',
                    success: function() { loadCurrentMembers(); loadAvailableUsers(); }
                });
            }
        });
    });

    $('#form-share-board').submit(function(e) {
        e.preventDefault();
        $.post("{{ route('kanban.board.share') }}", $(this).serialize(), function() {
            loadCurrentMembers();
            loadAvailableUsers();
            $('#form-share-board')[0].reset();
            Swal.fire({ icon: 'success', title: 'Berhasil mengundang!', timer: 1200, showConfirmButton: false });
        });
    });

    $(document).on('click', '.btn-del-task', function() {
        let taskId = $(this).data('id');
        Swal.fire({ title: 'Hapus Tugas?', icon: 'warning', showCancelButton: true, confirmButtonColor: '#dc3545' }).then(res => {
            if(res.isConfirmed) {
                $.ajax({
                    url: `/kanban/task/delete/${taskId}`,
                    method: 'DELETE',
                    success: function() { $(`#task-${taskId}`).fadeOut(300); }
                });
            }
        });
    });

    $('#btn-delete-board').click(function() {
        Swal.fire({ title: 'Hapus Seluruh Papan?', text: "Semua kolom & tugas di dalamnya akan musnah permanen!", icon: 'warning', showCancelButton: true, confirmButtonColor: '#dc3545' }).then(res => {
            if(res.isConfirmed) {
                $.ajax({ url: `/kanban/board/delete/${boardId}`, method: 'DELETE', success: function() { window.location.href = '/kanban'; } });
            }
        });
    });

    $('#form-add-board').on('submit', function(e) {
        e.preventDefault();
        $.ajax({
            url: "/kanban/board/store",
            method: 'POST',
            data: $(this).serialize(),
            success: function(response) {
                if(response.success) {
                    $('#addBoardModal').modal('hide');
                    window.location.href = `/kanban?board_id=${response.data.id}`;
                }
            }
        });
    });

    $('#btn-add-board-modal').on('click', function() {
        $('#form-add-board')[0].reset();
        $('#addBoardModal').modal('show');
    });

    loadBoard();
});
</script>
<style>
.animated-dropdown { animation: fadeIn 0.2s ease-out; }
@keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
.text-danger-hover:hover { color: #dc3545 !important; }
.text-info-hover:hover { color: #0284c7 !important; }
.style-placeholder { min-height: 80px; border-radius: 8px; }
</style>
@endpush
<div class="table-responsive">
    <table class="table table-hover align-middle mb-0">
        <thead class="table-light text-uppercase text-secondary">
            <tr>
                <th>No</th>
                <th>Mahasiswa (NIM)</th>
                <th>Instansi Tujuan</th>
                <th>Dosen Pembimbing</th>
                <th>Status Tahapan</th>
                <th>Jadwal Ujian</th>
                <th>Aksi</th>
            </tr>
        </thead>
        <tbody>
            @forelse($submissions as $item)
            <tr>
                <td>{{ $loop->iteration + ($submissions->currentPage() - 1) * $submissions->perPage() }}</td>
                <td>
                    <div class="fw-bold text-dark">{{ $item->mahasiswaRegistrasi->nama_mahasiswa ?? '-' }}</div>
                    <small class="text-muted">{{ $item->mahasiswaRegistrasi->nim ?? '-' }}</small><br>
                    <small class="text-danger">({{ $item->mahasiswaRegistrasi->nama_program_studi ?? '-' }})</small>
                </td>
                <td>
                    <div class="fw-semibold text-dark">{{ $item->nama_instansi }}</div>
                    <small class="text-muted text-truncate d-inline-block" style="max-width: 200px;">{{ $item->alamat_instansi }}</small>
                </td>
                <td>
                    @if($item->dosenPembimbing)
                        <span class="badge bg-success-subtle text-success">{{ $item->dosenPembimbing->nama_dosen }}</span>
                    @else
                        <span class="badge bg-warning-subtle text-warning">Belum Ditentukan</span>
                    @endif
                </td>
                <td>
                    @php
                        $status = $item->status;
                        $badgeClass = 'bg-secondary-subtle text-secondary';
                        $iconClass = 'ti ti-clock';
                        $label = $status;

                        if (!empty($item->catatan_admin)) {
                            $badgeClass = 'bg-danger-subtle text-danger border border-danger-subtle fw-bold';
                            $iconClass = 'ti ti-alert-circle';
                            $label = 'Ditolak / Revisi';
                        } elseif ($status == 'Verifikasi Berkas Admin') {
                            $badgeClass = 'bg-warning-subtle text-warning-emphasis border border-warning fw-bold';
                            $iconClass = 'ti ti-file-alert';
                            $label = 'Perlu Ditinjau (Semhas)';
                        } elseif ($status == 'Pelaksanaan PKL & Bimbingan') {
                            $badgeClass = 'bg-info-subtle text-info';
                            $iconClass = 'ti ti-briefcase';
                        } elseif ($status == 'Dijadwalkan Ujian') {
                            $badgeClass = 'bg-primary-subtle text-primary';
                            $iconClass = 'ti ti-calendar-event';
                        } elseif ($status == 'Selesai') {
                            $badgeClass = 'bg-success-subtle text-success';
                            $iconClass = 'ti ti-circle-check';
                        }
                    @endphp

                    <span class="badge {{ $badgeClass }} px-3 py-2 rounded-pill d-inline-flex align-items-center gap-1">
                        <i class="{{ $iconClass }}"></i>
                        <span>{{ $label }}</span>
                    </span>

                    @if($item->catatan_admin)
                        <div class="mt-1">
                            <span class="badge bg-danger text-white" style="font-size: 10px;" title="{{ $item->catatan_admin }}">
                                <i class="ti ti-info-circle me-1"> Ada Catatan Penolakan</i>
                            </span>
                        </div>
                    @endif
                </td>
                <td>
                    @if($item->tanggal_seminar)
                        <div class="small fw-bold text-dark"><i class="ti ti-calendar me-1"></i> {{ $item->tanggal_seminar->format('d M Y, H:i') }}</div>
                        <small class="text-muted"><i class="ti ti-map-pin me-1"></i> {{ $item->ruangan_seminar ?? 'Online / Ruangan' }}</small>
                        <div class="mt-2">
                            @forelse($item->dosenPenguji as $penguji)
                                <div class="small text-secondary">
                                    <span class="fw-semibold text-dark">{{ $penguji->nama_dosen }}</span> 
                                    <span class="badge bg-light text-dark border" style="font-size: 10px;">{{ $penguji->pivot->peran }}</span>
                                </div>
                            @empty
                                <span class="text-danger small">Penguji belum diplot</span>
                            @endforelse
                        </div>
                    @else
                        <span class="text-muted small">- Belum Ada Jadwal -</span>
                    @endif
                    @if(in_array($item->status, ['Pelaksanaan PKL & Bimbingan', 'Verifikasi Berkas Admin', 'Dijadwalkan Ujian'], true))
                        <button type="button" class="btn btn-sm btn-success btn-terapkan-usulan mt-2" data-id="{{ $item->id }}" data-dosen="{{ $item->dosen_pembimbing_id }}" data-tanggal="{{ $item->usulan_tanggal_seminar ?: $item->tanggal_seminar?->format('Y-m-d\TH:i') }}" data-ruangan="{{ $item->usulan_ruangan_seminar ?: $item->ruangan_seminar }}" data-usulan-pembimbing="{{ $item->usulan_nama_pembimbing }}">Tinjau plotting / jadwal</button>
                    @endif
                    @php($aktivitasTerakhir = $item->logAktivitas->sortByDesc('created_at')->take(4))
                    @if($aktivitasTerakhir->isNotEmpty())
                        <details class="mt-2 small">
                            <summary class="text-primary fw-semibold" style="cursor:pointer">Riwayat aktivitas</summary>
                            <div class="mt-2 border-start border-2 ps-2">
                                @foreach($aktivitasTerakhir as $aktivitas)
                                    <div class="mb-2">
                                        <strong>{{ $aktivitas->aksi }}</strong><br>
                                        <span class="text-muted">{{ $aktivitas->created_at?->format('d M Y H:i') }} · {{ $aktivitas->aktor?->name ?? 'Pengguna' }}</span>
                                        @if($aktivitas->deskripsi)<br><span>{{ $aktivitas->deskripsi }}</span>@endif
                                    </div>
                                @endforeach
                            </div>
                        </details>
                    @endif
                </td>
                <td style="white-space: nowrap;">
                    <div class="d-flex align-items-center gap-1">
                        <button type="button" class="btn btn-sm btn-light border text-primary btn-kelola" data-id="{{ $item->id }}" title="Plotting Pembimbing/Jadwal">
                            <i class="ti ti-edit"></i>
                        </button>
                        <button type="button" class="btn btn-sm btn-light border text-warning btn-penilaian-dinamis" data-id="{{ $item->id }}" title="Nilai Komponen Seminar">
                            <i class="ti ti-award"></i>
                        </button>
                        <button type="button" class="btn btn-sm btn-light border text-info btn-jadwal" data-id="{{ $item->id }}" title="Atur Jadwal & Penguji">
                            <i class="ti ti-calendar-event"></i>
                        </button>
                        <button type="button" class="btn btn-sm btn-info text-white fw-semibold btn-verifikasi-berkas shadow-sm" 
                                data-id="{{ $item->id }}" 
                                data-nama="{{ $item->mahasiswaRegistrasi->nama_mahasiswa ?? '-' }}"
                                data-logbook="{{ $item->berkas_logbook ? url('pkl/view-file/' . $item->berkas_logbook) : '' }}"
                                data-bimbingan="{{ $item->berkas_kartu_bimbingan ? url('pkl/view-file/' . $item->berkas_kartu_bimbingan) : '' }}"
                                data-laporan="{{ $item->berkas_draft_laporan ? url('pkl/view-file/' . $item->berkas_draft_laporan) : '' }}"
                                data-pamong="{{ $item->berkas_nilai_pamong ? url('pkl/view-file/' . $item->berkas_nilai_pamong) : '' }}"
                                data-persetujuan="{{ $item->berkas_persetujuan_pamong ? url('pkl/view-file/' . $item->berkas_persetujuan_pamong) : '' }}"
                                title="Verifikasi 6 Berkas Seminar">
                            <i class="ti ti-file-check"></i> Cek Berkas
                        </button>
                    </div>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="7" class="text-center py-4 text-muted">Belum ada data pengajuan PKL yang ditemukan.</td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>

<div class="mt-4 d-flex justify-content-end">
    {{ $submissions->withQueryString()->links() }}
</div>

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
                        $stageAction = match ($status) {
                            'Pengajuan & Verifikasi Admin' => 'manage',
                            'Pelaksanaan PKL & Bimbingan' => 'manage',
                            'Verifikasi Berkas Admin' => 'documents-verify',
                            'Dijadwalkan Ujian' => 'schedule',
                            'Ujian Seminar & Penilaian' => 'grades',
                            'Revisi Laporan' => 'documents-view',
                            'Selesai' => 'documents-view',
                            default => 'manage',
                        };

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

                    <button type="button" class="btn btn-link p-0 border-0 text-decoration-none btn-status-stage" data-action="{{ $stageAction }}" title="Klik untuk membuka tindakan pada tahap ini">
                    <span class="badge {{ $badgeClass }} px-3 py-2 rounded-pill d-inline-flex align-items-center gap-1">
                        <i class="{{ $iconClass }}"></i>
                        <span>{{ $label }}</span>
                    </span>
                    <i class="ti ti-external-link ms-1 small text-muted" aria-hidden="true"></i>
                    </button>

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
                                @if($item->ruangan_seminar)
                                    <a class="btn btn-sm btn-outline-primary mt-2" href="{{ route('pkl.invitation', $item->id) }}" target="_blank" rel="noopener">
                                        <i class="ti ti-printer me-1"></i> Undangan
                                    </a>
                                @endif
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
                    @php
                        $aktivitasTerakhir = $item->logAktivitas->sortByDesc('created_at')->take(4);
                    @endphp
                    @if($aktivitasTerakhir->isNotEmpty())
                        <details class="mt-2 small">
                            <summary class="text-primary fw-semibold" style="cursor:pointer">Riwayat aktivitas</summary>
                            <div class="mt-2 border-start border-2 ps-2">
                                @foreach($aktivitasTerakhir as $aktivitas)
                                    <div class="mb-2">
                                        <strong>{{ $aktivitas->aksi }}</strong><br>
                                        <span class="text-muted">{{ $aktivitas->created_at?->format('d M Y H:i') }} · {{ $aktivitas->aktor?->name ?? 'Pengguna' }}</span>
                                        @if($aktivitas->deskripsi)
                                            <br><span>{{ $aktivitas->deskripsi }}</span>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        </details>
                    @endif
                </td>
                <td style="white-space: nowrap;">
                    <div class="d-flex align-items-center gap-1">
                        @php
                            $documentLabels = [
                                'proposal' => 'Proposal ke Tempat PKL',
                                'surat_jalan_pkl' => 'Surat Jalan PKL',
                                'surat_balasan_instansi' => 'Surat Balasan Instansi',
                                'acc_pembimbing_awal' => 'ACC Pembimbing Awal',
                                'berkas_bukti_survei' => 'Bukti Survei PKL',
                                'berkas_surat_balasan' => 'Surat Balasan Instansi (Arsip Lama)',
                                'berkas_logbook' => 'Logbook PKL',
                                'berkas_nilai_pamong' => 'Lembar Penilaian Pamong',
                                'berkas_persetujuan_pamong' => 'Surat Persetujuan Pamong',
                                'draft_laporan' => 'Draft Laporan PKL',
                                'kartu_konsultasi' => 'Kartu Konsultasi',
                                'berkas_kartu_bimbingan' => 'Kartu Konsultasi',
                                'transkrip_sementara' => 'Transkrip Sementara',
                                'formulir_pendaftaran_ujian' => 'Formulir Pendaftaran Ujian',
                                'berita_acara_ujian' => 'Berita Acara Ujian',
                                'formulir_nilai' => 'Formulir Nilai',
                                'permohonan_revisi' => 'Formulir Permohonan Revisi',
                                'laporan_final' => 'Laporan Final',
                                'lampiran_teknis' => 'Lampiran Teknis',
                                'acc_pembimbing_akhir' => 'ACC Pembimbing Akhir',
                                'rekap_nilai' => 'Rekap Nilai',
                                'berkas_laporan_revisi' => 'Laporan Revisi',
                                'berkas_lembar_pengesahan' => 'Lembar Pengesahan',
                            ];
                            $finalDocumentKinds = [
                                'formulir_pendaftaran_ujian',
                                'berita_acara_ujian',
                                'formulir_nilai',
                                'permohonan_revisi',
                                'laporan_final',
                                'lampiran_teknis',
                                'acc_pembimbing_akhir',
                                'rekap_nilai',
                            ];
                            $documents = $item->berkasSeminar
                                ->filter(fn ($document) => filled($document->path_berkas))
                                ->map(fn ($document) => [
                                    'kind' => $document->jenis_berkas,
                                    'label' => $documentLabels[$document->jenis_berkas] ?? ucfirst(str_replace('_', ' ', $document->jenis_berkas)),
                                    'url' => url('pkl/view-file/' . $document->path_berkas),
                                    'status' => $document->status_verifikasi ?: 'Belum diverifikasi',
                                    'group' => in_array($document->jenis_berkas, $finalDocumentKinds, true) ? 'final' : 'previous',
                                ])
                                ->values();
                            foreach ([
                                'berkas_proposal' => ['Proposal ke Tempat PKL', 'proposal'],
                                'berkas_bukti_survei' => ['Bukti Survei PKL', 'berkas_bukti_survei'],
                                'berkas_surat_balasan' => ['Surat Balasan Instansi (Arsip Lama)', 'berkas_surat_balasan'],
                                'berkas_logbook' => ['Logbook PKL', 'berkas_logbook'],
                                'berkas_draft_laporan' => ['Draft Laporan PKL', 'draft_laporan'],
                                'berkas_kartu_bimbingan' => ['Kartu Konsultasi', 'kartu_konsultasi'],
                                'berkas_nilai_pamong' => ['Lembar Penilaian Pamong', 'berkas_nilai_pamong'],
                                'berkas_persetujuan_pamong' => ['Surat Persetujuan Pamong', 'berkas_persetujuan_pamong'],
                                'berkas_laporan_revisi' => ['Laporan Revisi', 'berkas_laporan_revisi'],
                                'berkas_lembar_pengesahan' => ['Lembar Pengesahan', 'berkas_lembar_pengesahan'],
                            ] as $field => [$documentLabel, $documentKind]) {
                                if (filled($item->{$field}) && ! $documents->contains('kind', $documentKind)) {
                                    $documents->push([
                                        'kind' => $documentKind,
                                        'label' => $documentLabel,
                                        'url' => url('pkl/view-file/' . $item->{$field}),
                                        'status' => 'Tersimpan',
                                        'group' => 'previous',
                                    ]);
                                }
                            }
                            if ($item->status === 'Revisi Laporan') {
                                foreach ($finalDocumentKinds as $kind) {
                                    if (! $documents->contains('kind', $kind)) {
                                        $documents->push([
                                            'kind' => $kind,
                                            'label' => $documentLabels[$kind] ?? ucfirst(str_replace('_', ' ', $kind)),
                                            'url' => null,
                                            'status' => 'Belum diunggah',
                                            'group' => 'final',
                                        ]);
                                    }
                                }
                            }
                        @endphp
                        <a href="{{ route('pkl.dosen.bimbingan.detail', $item->id) }}" class="btn btn-sm btn-light border text-secondary" title="Detail Bimbingan">
                            <i class="ti ti-eye"></i>
                        </a>
                        <button type="button" class="btn btn-sm btn-light border text-primary btn-kelola" data-id="{{ $item->id }}" title="Edit pengajuan / tentukan dosen pembimbing">
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
                                data-status="{{ $item->status }}"
                                data-documents="{{ json_encode($documents, JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_TAG | JSON_HEX_AMP) }}"
                                data-can-verify="{{ $item->status === 'Verifikasi Berkas Admin' ? 'true' : 'false' }}"
                                title="Lihat seluruh berkas mahasiswa">
                            <i class="ti ti-file-check"></i> Cek Berkas
                        </button>
                        <button type="button" class="btn btn-sm btn-light border text-danger btn-hapus-pkl" 
                                data-id="{{ $item->id }}" 
                                data-nama="{{ $item->mahasiswaRegistrasi->nama_mahasiswa ?? '-' }}"
                                title="Hapus Data PKL & Seluruh Berkas">
                            <i class="ti ti-trash"></i>
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

@php
    // Fallback jika $dosenId dari controller bernilai null
    if (empty($dosenId)) {
        $userId = Auth::id();
        $userClaim = \App\Models\Otentikasi\UserClaim::where('user_id', $userId)
            ->where('role_type', 'dosen')
            ->where('status_claim', 'approved')
            ->first();

        $dosenId = $userClaim ? ($userClaim->feeder_id ?? $userClaim->feeder_id ?? $userClaim->feeder_id) : (auth()->user()->dosen->id_dosen ?? '');
    }
@endphp

@forelse($komponen as $item)
    <div class="mb-3 p-3 border rounded-3 bg-light-subtle">
        <label class="form-label fw-bold text-dark mb-1">
            {{ $item->nama_komponen ?? $item->komponen ?? 'Komponen Penilaian' }} 
            <span class="badge bg-secondary-subtle text-secondary ms-1">Bobot: {{ $item->bobot }}%</span>
        </label>
        @if(!empty($item->deskripsi))
            <p class="small text-muted mb-2">{{ $item->deskripsi }}</p>
        @endif
        
        @php
            // Filter nilai yang diisi khusus oleh Dosen ini
            $existingDetail = $item->detailNilai
                ->where('dosen_id', $dosenId)
                ->first();
        @endphp

        <!-- Format input name: nilai[dosen_id][komponen_id] -->
        <input type="number" 
               name="nilai[{{ $dosenId }}][{{ $item->id }}]" 
               class="form-control" 
               min="0" max="100" step="0.01" 
               value="{{ $existingDetail->nilai_angka ?? '' }}" 
               placeholder="Masukkan nilai (0 - 100)" 
               required>
    </div>
@empty
    <div class="text-center py-3 text-muted fst-italic">
        Belum ada master komponen penilaian PKL yang terdaftar di database. Silakan hubungi Administrator.
    </div>
@endforelse
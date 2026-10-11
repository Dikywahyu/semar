<?php

namespace App\Http\Controllers\Kanban;

use App\Http\Controllers\Controller;
use App\Models\Kanban\KanbanBoard;
use App\Models\Kanban\KanbanStage;
use App\Models\User;
use App\Models\Kanban\KanbanTask;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class KanbanController extends Controller
{
    /**
     * Tampilkan halaman utama papan Kanban.
     */
    public function index(Request $request)
    {
        $boardIdParam = $request->query('board_id');
        $user = Auth::user();
    
        // Ambil Board Kerja Aktif
        $currentBoard = KanbanBoard::where('id', $boardIdParam ?: 0)
            ->where(function($q) use ($user) {
                $q->where('user_id', $user->id)->orWhereHas('members', fn($query) => $query->where('users.id', $user->id));
            })->first() ?: KanbanBoard::where('user_id', $user->id)->first();
    
        // 1. KONDISI A: Jika user sama sekali belum punya papan kerja
        if (!$currentBoard) {
            try {
                DB::beginTransaction();
    
                $currentBoard = KanbanBoard::create([
                    'name' => 'Papan Utama', 
                    'user_id' => $user->id,
                    'description' => 'Papan kerja utama Anda.'
                ]);
    
                KanbanStage::create(['board_id' => $currentBoard->id, 'name' => 'Todo', 'position' => 0, 'color' => '#6c757d']);
                KanbanStage::create(['board_id' => $currentBoard->id, 'name' => 'In Progress', 'position' => 1, 'color' => '#0085db']);
                KanbanStage::create(['board_id' => $currentBoard->id, 'name' => 'Done', 'position' => 2, 'color' => '#13deb9']);
    
                DB::commit();
            } catch (\Exception $e) {
                DB::rollBack();
            }
        } 
        // 2. KONDISI B: Papan sudah ada (seperti Papan Utama Anda sekarang), tapi isinya kosong (0 kolom)
        else if ($currentBoard->stages()->count() === 0) {
            try {
                DB::beginTransaction();
    
                // Suntikkan 3 kolom default ke papan yang sedang aktif ini
                KanbanStage::create(['board_id' => $currentBoard->id, 'name' => 'Todo', 'position' => 0, 'color' => '#6c757d']);
                KanbanStage::create(['board_id' => $currentBoard->id, 'name' => 'In Progress', 'position' => 1, 'color' => '#0085db']);
                KanbanStage::create(['board_id' => $currentBoard->id, 'name' => 'Done', 'position' => 2, 'color' => '#13deb9']);
    
                DB::commit();
            } catch (\Exception $e) {
                DB::rollBack();
            }
        }
    
        // CEK ROLE USER SAAT INI
        $userRole = 'viewer';
        if ($currentBoard->user_id == $user->id) {
            $userRole = 'owner';
        } else {
            $member = $currentBoard->members()->where('users.id', $user->id)->first();
            $userRole = $member ? $member->pivot->role : 'viewer';
        }
    
        $allBoards = KanbanBoard::where('user_id', $user->id)->orWhereHas('members', fn($q) => $q->where('users.id', $user->id))->get();
        
        $stages = $currentBoard->stages()->with('tasks')->get();
    
        return view('kanban.board', [
            'id' => $currentBoard->id,
            'allBoards' => $allBoards,
            'userRole' => $userRole, 
            'stages' => $stages
        ]);
    }
    
    // FUNGSI BARU: Ambil List Member Aktif
    public function getBoardMembers($id) {
        $board = KanbanBoard::where('id', $id)->where('user_id', Auth::id())->firstOrFail();
        $members = $board->members()->select('users.id', 'name', 'email')->get();
        return response()->json(['success' => true, 'data' => $members]);
    }
    
    // FUNGSI BARU: Update Role Member
    public function updateMemberRole(Request $request) {
        $board = KanbanBoard::where('id', $request->board_id)->where('user_id', Auth::id())->firstOrFail();
        $board->members()->updateExistingPivot($request->user_id, ['role' => $request->role]);
        return response()->json(['success' => true, 'message' => 'Otoritas diperbarui']);
    }
    
    // FUNGSI BARU: Hapus Member
    public function removeMember($board_id, $user_id) {
        $board = KanbanBoard::where('id', $board_id)->where('user_id', Auth::id())->firstOrFail();
        $board->members()->detach($user_id);
        return response()->json(['success' => true, 'message' => 'Akses dicabut']);
    }
    
    /**
     * Mengambil data papan beserta stage dan tugas di dalamnya (AJAX).
     */
    public function getBoardData($id)
    {
        try {
            // Validasi: Pastikan yang mengakses adalah owner ATAU member terdaftar
            $board = KanbanBoard::where('id', $id)
                ->where(function($query) {
                    $query->where('user_id', Auth::id())
                          ->orWhereHas('members', function($q) {
                              $q->where('users.id', Auth::id());
                          });
                })
                ->with(['stages' => function($query) {
                    $query->orderBy('position', 'asc')->with(['tasks' => function($q) {
                        $q->orderBy('position', 'asc');
                    }]);
                }])
                ->firstOrFail();

            return response()->json([
                'success' => true,
                'data' => $board
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Papan tidak ditemukan atau Anda tidak memiliki akses.'
            ], 403);
        }
    }
    
    /**
     * TAMBAHAN KOLABORASI: Ambil daftar user lain yang belum bergabung ke board ini untuk dropdown Share.
     */
    public function getAvailableUsers($id)
    {
        try {
            $board = KanbanBoard::where('id', $id)->where('user_id', Auth::id())->firstOrFail();
            
            // Ambil ID user yang sudah menjadi member/kolaborator saat ini
            $currentMemberIds = $board->members()->pluck('users.id')->toArray();
            
            // Tambahkan ID owner agar tidak muncul di daftar pilihan undang
            $currentMemberIds[] = $board->user_id;

            // Ambil semua user aktif di sistem kecualikan yang sudah bergabung
            $users = User::whereNotIn('id', $currentMemberIds)
                         ->select('id', 'name', 'email')
                         ->get();

            return response()->json([
                'success' => true,
                'data' => $users
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil data user.'
            ], 500);
        }
    }

    /**
     * TAMBAHAN KOLABORASI: Simpan user baru ke tabel pivot (Share Board).
     */
    public function shareBoard(Request $request)
    {
        $request->validate([
            'board_id' => 'required|exists:kanban_boards,id',
            'user_id' => 'required|exists:users,id',
            'role' => 'required|in:member,viewer'
        ]);

        try {
            // Pastikan hanya OWNER asli board yang boleh mengundang orang lain
            $board = KanbanBoard::where('id', $request->board_id)
                                ->where('user_id', Auth::id())
                                ->firstOrFail();

            // Pasang user ke tabel pivot kanban_board_members
            // menggunakan syncWithoutDetaching agar data kolaborator lama tidak terhapus
            $board->members()->syncWithoutDetaching([
                $request->user_id => ['role' => $request->role]
            ]);

            return response()->json([
                'success' => true,
                'message' => 'User berhasil diundang ke papan kerja Anda!'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal membagikan papan kerja: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * TAMBAHKAN FUNGSI BARU INI:
     * Menyimpan Papan Kanban Baru via AJAX
     */
    public function storeBoard(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);

        try {
            DB::beginTransaction();

            // 1. Buat Board Baru terikat dengan user yang login
            $board = KanbanBoard::create([
                'name' => $request->name,
                'description' => $request->description,
                'user_id' => Auth::id()
            ]);

            // 2. Buatkan 3 Kolom Status Default secara otomatis
            KanbanStage::create(['board_id' => $board->id, 'name' => 'Todo', 'position' => 0, 'color' => '#6c757d']);
            KanbanStage::create(['board_id' => $board->id, 'name' => 'In Progress', 'position' => 1, 'color' => '#0085db']);
            KanbanStage::create(['board_id' => $board->id, 'name' => 'Done', 'position' => 2, 'color' => '#13deb9']);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Papan kerja baru berhasil dibuat!',
                'data' => $board
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Gagal menyimpan papan: ' . $e->getMessage()
            ], 500);
        }
    }

     /**
     * Memperbarui data detail kartu tugas via AJAX.
     */
    public function updateTask(Request $request, $id)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'priority' => 'required|in:low,medium,high',
            'due_date' => 'nullable|date',
        ]);
    
        try {
            // Ambil data tugas beserta papan kerja terkait
            $task = KanbanTask::with('stage.board')->findOrFail($id);
            $board = $task->stage->board;
    
            // Validasi Otoritas: Owner ATAU Member terdaftar dengan role 'member'
            $isOwner = ($board->user_id == Auth::id());
            
            // PERBAIKAN DI SINI: Tambahkan nama tabel 'kanban_board_members.role'
            $isMember = $board->members()
                ->where('users.id', Auth::id())
                ->where('kanban_board_members.role', 'member')
                ->exists();
    
            if (!$isOwner && !$isMember) {
                return response()->json([
                    'success' => false,
                    'message' => 'Akses ditolak. Anda tidak memiliki otoritas di papan ini.'
                ], 403);
            }
    
            // Jalankan update
            $task->update([
                'title' => $request->title,
                'description' => $request->description,
                'priority' => $request->priority,
                'due_date' => $request->due_date,
            ]);
    
            return response()->json([
                'success' => true,
                'message' => 'Tugas berhasil diperbarui oleh kolaborator!'
            ]);
    
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal memperbarui tugas: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Simpan kartu tugas baru via AJAX.
     */
    public function storeTask(Request $request)
    {
        $request->validate([
            'stage_id' => 'required|exists:kanban_stages,id',
            'title' => 'required|string|max:255',
            'priority' => 'required|in:low,medium,high',
            'due_date' => 'nullable|date',
        ]);

        // Hitung posisi terakhir agar kartu baru berada di paling bawah kolom
        $maxPosition = KanbanTask::where('stage_id', $request->stage_id)->max('position') ?? 0;

        $task = KanbanTask::create([
            'stage_id' => $request->stage_id,
            'title' => $request->title,
            'description' => $request->description,
            'priority' => $request->priority,
            'due_date' => $request->due_date,
            'position' => $maxPosition + 1,
            'user_id' => Auth::id()
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Tugas berhasil ditambahkan!',
            'data' => $task
        ]);
    }

    /**
     * Handle urutan posisi kartu saat di-drag and drop (Full AJAX).
     */
    public function updateTaskPosition(Request $request)
    {
        $request->validate([
            'task_id' => 'required|exists:kanban_tasks,id',
            'target_stage_id' => 'required|exists:kanban_stages,id',
            'ordered_ids' => 'required|array', // Array susunan ID kartu baru di kolom tujuan
        ]);

        // Menggunakan Database Transaction untuk menjamin integritas data saat sorting masal
        DB::beginTransaction();
        try {
            // 1. Pindahkan kolom kartu utama ke kolom target
            $task = KanbanTask::findOrFail($request->task_id);
            $task->stage_id = $request->target_stage_id;
            $task->save();

            // 2. Susun ulang posisi seluruh kartu di kolom tersebut berdasarkan urutan drop baru
            foreach ($request->ordered_ids as $index => $id) {
                KanbanTask::where('id', $id)->update(['position' => $index]);
            }

            DB::commit();
            return response()->json(['success' => true, 'message' => 'Posisi tugas berhasil diperbarui.']);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => 'Gagal memperbarui posisi.'], 500);
        }
    }

    /**
     * Hapus kartu tugas via AJAX.
     */
    public function deleteTask($id)
    {
        try {
            $task = KanbanTask::findOrFail($id);
            $task->delete();

            return response()->json(['success' => true, 'message' => 'Tugas berhasil dihapus.']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Gagal menghapus tugas.'], 500);
        }
    }
    
    /**
     * Hapus papan kerja beserta seluruh stage dan task di dalamnya via AJAX.
     */
    public function deleteBoard($id)
    {
        // 1. Proteksi: Cari papan pertama (Papan Utama) milik user
        $firstBoard = KanbanBoard::where('user_id', Auth::id())->orderBy('id', 'asc')->first();
    
        // Jika user mencoba menghapus papan utama, tolak request
        if ($firstBoard && $firstBoard->id == $id) {
            return response()->json([
                'success' => false,
                'message' => 'Papan Utama tidak boleh dihapus demi stabilitas sistem.'
            ], 403);
        }
    
        try {
            DB::beginTransaction();
    
            // 2. Cari board yang akan dihapus
            $board = KanbanBoard::where('id', $id)->where('user_id', Auth::id())->firstOrFail();
    
            // Ambil semua ID stage di bawah board ini
            $stageIds = KanbanStage::where('board_id', $board->id)->pluck('id');
    
            // Hapus semua kartu tugas (tasks) yang terikat pada stages tersebut
            KanbanTask::whereIn('stage_id', $stageIds)->delete();
    
            // Hapus semua stages
            KanbanStage::where('board_id', $board->id)->delete();
    
            // Hapus papan kerja utama
            $board->delete();
    
            DB::commit();
    
            return response()->json([
                'success' => true,
                'message' => 'Papan kerja berhasil dihapus secara permanen.'
            ]);
    
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Gagal menghapus papan: ' . $e->getMessage()
            ], 500);
        }
    }
}
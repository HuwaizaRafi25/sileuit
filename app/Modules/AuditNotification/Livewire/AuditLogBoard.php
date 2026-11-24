<?php

namespace App\Modules\AuditNotification\Livewire;

use App\Modules\AuditNotification\Models\AuditLog;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.app')]
class AuditLogBoard extends Component
{
    use WithPagination;

    public string $q = '';

    public string $aksi = '';

    public function mount(): void
    {
        abort_unless(auth()->user()->can('lihat-audit'), 403);
    }

    public function render()
    {
        $q = AuditLog::with('user')->latest('waktu');
        if ($this->q !== '') {
            $q->where(function ($w) {
                $w->where('aksi', 'like', "%{$this->q}%")
                    ->orWhere('entitas', 'like', "%{$this->q}%")
                    ->orWhereHas('user', fn ($u) => $u->where('name', 'like', "%{$this->q}%"));
            });
        }
        if ($this->aksi !== '') {
            $q->where('aksi', $this->aksi);
        }

        return view('livewire.audit-log-board', [
            'logs' => $q->paginate(25),
            'aksiList' => AuditLog::select('aksi')->distinct()->orderBy('aksi')->pluck('aksi'),
        ]);
    }
}

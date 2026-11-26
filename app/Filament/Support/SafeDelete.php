<?php

namespace App\Filament\Support;

use Closure;
use Filament\Actions\DeleteAction;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\QueryException;

class SafeDelete
{
    /**
     * Create a delete action that blocks unsafe deletions and handles foreign-key constraints cleanly.
     *
     * @param  (Closure(Model): ?string)|null  $guardReason
     * @param  (Closure(Model): void)|null  $afterDelete
     */
    public static function make(?Closure $guardReason = null, ?Closure $afterDelete = null): DeleteAction
    {
        return DeleteAction::make()
            ->label('Hapus')
            ->failureNotification(null)
            ->using(function (Model $record) use ($guardReason, $afterDelete): bool {
                if ($guardReason !== null) {
                    $reason = $guardReason($record);
                    if (filled($reason)) {
                        Notification::make()
                            ->title('Data tidak dapat dihapus')
                            ->body($reason)
                            ->warning()
                            ->send();

                        return false;
                    }
                }

                try {
                    $deleted = (bool) $record->delete();
                    if ($deleted && $afterDelete !== null) {
                        $afterDelete($record);
                    }

                    return $deleted;
                } catch (QueryException) {
                    Notification::make()
                        ->title('Data masih digunakan oleh transaksi lain')
                        ->body('Penghapusan dibatalkan karena data masih terhubung dengan kontrak, stok, atau transaksi.')
                        ->warning()
                        ->send();

                    return false;
                }
            });
    }
}

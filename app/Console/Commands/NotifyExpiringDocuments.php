<?php

namespace App\Console\Commands;

use App\Enums\DocumentStatus;
use App\Models\Employee;
use App\Models\EmployeeDocument;
use App\Models\User;
use App\Notifications\DocumentsExpiring;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Notification;

/**
 * Avisa a los administradores una vez cuando un documento entra en "por vencer"
 * y otra cuando vence. Corre todos los días desde el programador de tareas.
 */
class NotifyExpiringDocuments extends Command
{
    protected $signature = 'personal:avisar-vencimientos';

    protected $description = 'Avisa al dueño qué documentos del personal vencen pronto o vencieron';

    public function handle(): int
    {
        $due = Employee::query()->active()->with(['documents.type.gatedCommunity', 'documents.employee'])->get()
            ->flatMap(fn (Employee $e) => $e->currentDocuments())
            ->filter(fn (EmployeeDocument $d) => match ($d->status()) {
                DocumentStatus::PorVencer => $d->expiring_notified_at === null,
                DocumentStatus::Vencido => $d->expired_notified_at === null,
                default => false,
            })
            ->sortBy('expires_on')
            ->values();

        if ($due->isEmpty()) {
            $this->info('No hay vencimientos nuevos para avisar.');

            return self::SUCCESS;
        }

        $admins = User::query()->admins()->get();
        Notification::send($admins, new DocumentsExpiring($due));

        foreach ($due as $document) {
            $document->update($document->status() === DocumentStatus::Vencido
                ? ['expired_notified_at' => now(), 'expiring_notified_at' => $document->expiring_notified_at ?? now()]
                : ['expiring_notified_at' => now()]);
        }
        $this->info("Avisados {$due->count()} documento(s) a {$admins->count()} administrador(es).");

        return self::SUCCESS;
    }
}

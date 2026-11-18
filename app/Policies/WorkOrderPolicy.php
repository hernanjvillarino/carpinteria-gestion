<?php

namespace App\Policies;

use App\Enums\Role;
use App\Models\User;
use App\Models\WorkOrder;

class WorkOrderPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin();
    }

    public function view(User $user, WorkOrder $order): bool
    {
        return match ($user->role) {
            Role::Admin => true,
            Role::Carpintero => $order->isAssignedTo($user),
            Role::Cliente => $order->client?->user_id === $user->id,
        };
    }

    /** Registrar avances, cambiar etapa y subir fotos. */
    public function progress(User $user, WorkOrder $order): bool
    {
        return $user->isAdmin() || ($user->role === Role::Carpintero && $order->isAssignedTo($user));
    }

    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    public function update(User $user, WorkOrder $order): bool
    {
        return $user->isAdmin();
    }

    public function delete(User $user, WorkOrder $order): bool
    {
        return $user->isAdmin();
    }
}

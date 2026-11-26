<?php

namespace App\Models;

use App\Enums\Role;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use NotificationChannels\WebPush\HasPushSubscriptions;

#[Fillable(['name', 'email', 'phone', 'password', 'role', 'is_active'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, HasPushSubscriptions, Notifiable;

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'role' => Role::class,
            'is_active' => 'boolean',
        ];
    }

    public function hasRole(Role ...$roles): bool
    {
        return in_array($this->role, $roles, true);
    }

    public function isAdmin(): bool
    {
        return $this->role === Role::Admin;
    }

    /** Trabajos asignados (carpinteros). */
    public function assignedWorkOrders(): BelongsToMany
    {
        return $this->belongsToMany(WorkOrder::class, 'work_order_user')->withPivot('is_lead')->withTimestamps();
    }

    /** Avances que cargó (para saber su última actividad). */
    public function workOrderUpdates(): HasMany
    {
        return $this->hasMany(WorkOrderUpdate::class);
    }

    /** Ficha de cliente vinculada a este acceso. */
    /** Celulares con la app nativa que reciben avisos push por FCM. */
    public function deviceTokens(): HasMany
    {
        return $this->hasMany(DeviceToken::class);
    }

    public function client(): HasOne
    {
        return $this->hasOne(Client::class);
    }

    /** Ficha de personal (documentación para entrar a los barrios). */
    public function employee(): HasOne
    {
        return $this->hasOne(Employee::class);
    }

    public function scopeCarpinteros($query)
    {
        return $query->where('role', Role::Carpintero)->where('is_active', true);
    }

    /** Dueños / administradores activos: reciben los avisos del taller. */
    public function scopeAdmins($query)
    {
        return $query->where('role', Role::Admin)->where('is_active', true);
    }
}

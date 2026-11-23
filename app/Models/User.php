<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, HasRoles;

    // Compatible con Laravel 12 (los atributos PHP #[Fillable]/#[Hidden] son exclusivos de L13)
    protected $fillable = ['name', 'email', 'password'];
    protected $hidden   = ['password', 'remember_token'];

    protected function casts(): array
    {
        return [
            'email_verified_at'     => 'datetime',
            'password'              => 'hashed',
            'notification_settings' => 'array',
        ];
    }

    public static function defaultNotificationPrefs(): array
    {
        return [
            'silent_mode'          => false,
            'compact_mode'         => false,
            'show_floating_banner' => true,
            'banner_duration_sec'  => 8,
            'show_login_summary'   => true,
            'windows_desktop'      => true,
            'only_high_priority'   => false,
            'categories'           => collect(array_keys(UserNotification::CATEGORIAS))
                ->mapWithKeys(fn (string $k) => [$k => true])
                ->all(),
        ];
    }

    public function notificationPrefs(): array
    {
        $defaults = self::defaultNotificationPrefs();
        $saved = is_array($this->notification_settings) ? $this->notification_settings : [];

        $merged = array_merge($defaults, array_intersect_key($saved, $defaults));
        $merged['silent_mode']          = (bool) $merged['silent_mode'];
        $merged['compact_mode']         = (bool) $merged['compact_mode'];
        $merged['show_floating_banner'] = (bool) $merged['show_floating_banner'];
        $merged['show_login_summary']   = (bool) $merged['show_login_summary'];
        $merged['windows_desktop']      = (bool) $merged['windows_desktop'];
        $merged['only_high_priority']   = (bool) $merged['only_high_priority'];
        $merged['banner_duration_sec']  = in_array((int) $merged['banner_duration_sec'], [5, 8, 12, 15], true)
            ? (int) $merged['banner_duration_sec']
            : 8;

        $savedCats = is_array($saved['categories'] ?? null) ? $saved['categories'] : [];
        $cats = [];
        foreach ($defaults['categories'] as $slug => $defaultVal) {
            $cats[$slug] = array_key_exists($slug, $savedCats) ? (bool) $savedCats[$slug] : $defaultVal;
        }
        $merged['categories'] = $cats;

        return $merged;
    }

    public function consultant()
    {
        return $this->hasOne(Consultant::class);
    }

    public function userNotifications()
    {
        return $this->hasMany(UserNotification::class)->latest();
    }
}

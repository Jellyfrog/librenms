<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * App\Models\Dashboard
 *
 * @property int $dashboard_id
 * @property int $user_id
 * @property string $dashboard_name
 * @property int $access
 * @property-read \App\Models\User $user
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\UserWidget[] $widgets
 * @property-read int|null $widgets_count
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Dashboard allAvailable($user)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Dashboard newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Dashboard newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Dashboard query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Dashboard whereAccess($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Dashboard whereDashboardId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Dashboard whereDashboardName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Dashboard whereUserId($value)
 * @mixin \Eloquent
 */
class Dashboard extends Model
{
    public $timestamps = false;
    protected $primaryKey = 'dashboard_id';
    protected $fillable = ['user_id', 'dashboard_name', 'access'];

    // ---- Helper Functions ---

    /**
     * @param User $user
     * @return bool
     */
    public function canRead($user)
    {
        return $this->user_id == $user->user_id || $this->access > 0;
    }

    /**
     * @param User $user
     * @return bool
     */
    public function canWrite($user)
    {
        return $this->user_id == $user->user_id || $this->access > 1;
    }

    // ---- Query scopes ----

    /**
     * @param Builder $query
     * @param User $user
     * @return Builder|static
     */
    public function scopeAllAvailable(Builder $query, $user)
    {
        return $query->where('user_id', $user->user_id)
            ->orWhere('access', '>', 0);
    }

    // ---- Define Relationships ----

    public function user()
    {
        return $this->belongsTo(\App\Models\User::class, 'user_id');
    }

    public function widgets()
    {
        return $this->hasMany(\App\Models\UserWidget::class, 'dashboard_id');
    }
}

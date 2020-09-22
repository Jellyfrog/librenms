<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * App\Models\UserWidget
 *
 * @property int $user_widget_id
 * @property int $user_id
 * @property int $widget_id
 * @property int $col
 * @property int $row
 * @property int $size_x
 * @property int $size_y
 * @property string $title
 * @property int $refresh
 * @property array $settings
 * @property int $dashboard_id
 * @property-read \App\Models\Dashboard $dashboard
 * @property-read \App\Models\User $user
 * @property-read \App\Models\Widget|null $widget
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\UserWidget newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\UserWidget newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\UserWidget query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\UserWidget whereCol($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\UserWidget whereDashboardId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\UserWidget whereRefresh($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\UserWidget whereRow($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\UserWidget whereSettings($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\UserWidget whereSizeX($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\UserWidget whereSizeY($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\UserWidget whereTitle($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\UserWidget whereUserId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\UserWidget whereUserWidgetId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\UserWidget whereWidgetId($value)
 * @mixin \Eloquent
 */
class UserWidget extends Model
{
    public $timestamps = false;
    protected $table = 'users_widgets';
    protected $primaryKey = 'user_widget_id';
    protected $fillable = ['user_id', 'widget_id', 'col', 'row', 'size_x', 'size_y', 'title', 'refresh', 'settings', 'dashboard_id'];
    protected $casts = ['settings' => 'array'];

    // ---- Define Relationships ----

    public function user()
    {
        return $this->belongsTo(\App\Models\User::class, 'user_id');
    }

    public function widget()
    {
        return $this->hasOne(\App\Models\Widget::class, 'widget_id');
    }

    public function dashboard()
    {
        return $this->belongsTo(\App\Models\Dashboard::class, 'dashboard_id');
    }
}

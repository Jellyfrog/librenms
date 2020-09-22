<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * App\Models\NotificationAttrib
 *
 * @property int $attrib_id
 * @property int $notifications_id
 * @property int $user_id
 * @property string $key
 * @property string $value
 * @property-read \App\Models\Notification $notification
 * @property-read \App\Models\User $user
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\NotificationAttrib newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\NotificationAttrib newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\NotificationAttrib query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\NotificationAttrib whereAttribId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\NotificationAttrib whereKey($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\NotificationAttrib whereNotificationsId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\NotificationAttrib whereUserId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\NotificationAttrib whereValue($value)
 * @mixin \Eloquent
 */
class NotificationAttrib extends Model
{
    public $timestamps = false;
    protected $table = 'notifications_attribs';
    protected $primaryKey = 'attrib_id';
    protected $fillable = ['notifications_id', 'user_id', 'key', 'value'];

    // ---- Define Relationships ----

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user()
    {
        return $this->belongsTo(\App\Models\User::class, 'user_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function notification()
    {
        return $this->belongsTo(\App\Models\Notification::class, 'notifications_id');
    }
}

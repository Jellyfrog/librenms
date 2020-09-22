<?php
/**
 * Widget.php
 *
 * -Description-
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @link       http://librenms.org
 * @copyright  2018 Tony Murray
 * @author     Tony Murray <murraytony@gmail.com>
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * App\Models\Widget
 *
 * @property int $widget_id
 * @property string $widget_title
 * @property string $widget
 * @property string $base_dimensions
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Widget newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Widget newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Widget query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Widget whereBaseDimensions($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Widget whereWidget($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Widget whereWidgetId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Widget whereWidgetTitle($value)
 * @mixin \Eloquent
 */
class Widget extends Model
{
    public $timestamps = false;
    protected $primaryKey = 'widget_id';
    protected $fillable = ['widget_title', 'widget', 'base_dimensions'];
}

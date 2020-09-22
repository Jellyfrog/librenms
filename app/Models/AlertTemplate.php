<?php
/**
 * app/Models/AlertTemplate.php
 *
 * Model for access to alert_templates table data
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
 * @copyright  2018 Neil Lathwood
 * @author     Neil Lathwood <gh+n@laf.io>
 */

namespace App\Models;

/**
 * App\Models\AlertTemplate
 *
 * @property int $id
 * @property string $name
 * @property string $template
 * @property string|null $title
 * @property string|null $title_rec
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\AlertTemplateMap[] $map
 * @property-read int|null $map_count
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\AlertTemplate newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\AlertTemplate newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\AlertTemplate query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\AlertTemplate whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\AlertTemplate whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\AlertTemplate whereTemplate($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\AlertTemplate whereTitle($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\AlertTemplate whereTitleRec($value)
 * @mixin \Eloquent
 */
class AlertTemplate extends BaseModel
{
    public $timestamps = false;

    // ---- Define Relationships ----

    public function map()
    {
        return $this->hasMany(\App\Models\AlertTemplateMap::class, 'alert_templates_id', 'id');
    }
}

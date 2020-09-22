<?php
/**
 * app/Models/AlertTemplateMap.php
 *
 * Model for access to alert_template_map table data
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
 * App\Models\AlertTemplateMap
 *
 * @property int $id
 * @property int $alert_templates_id
 * @property int $alert_rule_id
 * @property-read \App\Models\AlertTemplate $template
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\AlertTemplateMap newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\AlertTemplateMap newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\AlertTemplateMap query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\AlertTemplateMap whereAlertRuleId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\AlertTemplateMap whereAlertTemplatesId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\AlertTemplateMap whereId($value)
 * @mixin \Eloquent
 */
class AlertTemplateMap extends BaseModel
{
    protected $table = 'alert_template_map';
    public $timestamps = false;

    // ---- Define Relationships ----

    public function template()
    {
        return $this->belongsTo(\App\Models\AlertTemplate::class, 'alert_templates_id');
    }
}

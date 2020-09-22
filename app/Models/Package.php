<?php
/**
 * Package.php
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

/**
 * App\Models\Package
 *
 * @property int $pkg_id
 * @property int $device_id
 * @property string $name
 * @property string $manager
 * @property int $status
 * @property string $version
 * @property string $build
 * @property string $arch
 * @property int|null $size
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\DeviceRelatedModel hasAccess(\App\Models\User $user)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\DeviceRelatedModel inDeviceGroup($deviceGroup)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Package newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Package newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Package query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Package whereArch($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Package whereBuild($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Package whereDeviceId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Package whereManager($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Package whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Package wherePkgId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Package whereSize($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Package whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Package whereVersion($value)
 * @mixin \Eloquent
 */
class Package extends DeviceRelatedModel
{
    public $timestamps = false;
    protected $primaryKey = 'pkg_id';
}

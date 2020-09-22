<?php
/**
 * Bill.php
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
 * App\Models\Bill
 *
 * @property int $bill_id
 * @property string $bill_name
 * @property string $bill_type
 * @property int|null $bill_cdr
 * @property int $bill_day
 * @property int|null $bill_quota
 * @property int $rate_95th_in
 * @property int $rate_95th_out
 * @property int $rate_95th
 * @property string $dir_95th
 * @property int $total_data
 * @property int $total_data_in
 * @property int $total_data_out
 * @property int $rate_average_in
 * @property int $rate_average_out
 * @property int $rate_average
 * @property string $bill_last_calc
 * @property string $bill_custid
 * @property string $bill_ref
 * @property string $bill_notes
 * @property int $bill_autoadded
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Port[] $ports
 * @property-read int|null $ports_count
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Bill hasAccess(\App\Models\User $user)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Bill newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Bill newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Bill query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Bill whereBillAutoadded($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Bill whereBillCdr($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Bill whereBillCustid($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Bill whereBillDay($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Bill whereBillId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Bill whereBillLastCalc($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Bill whereBillName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Bill whereBillNotes($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Bill whereBillQuota($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Bill whereBillRef($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Bill whereBillType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Bill whereDir95th($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Bill whereRate95th($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Bill whereRate95thIn($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Bill whereRate95thOut($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Bill whereRateAverage($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Bill whereRateAverageIn($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Bill whereRateAverageOut($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Bill whereTotalData($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Bill whereTotalDataIn($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Bill whereTotalDataOut($value)
 * @mixin \Eloquent
 */
class Bill extends Model
{
    public $timestamps = false;
    protected $primaryKey = 'bill_id';

    // ---- Query Scopes ----

    public function scopeHasAccess($query, User $user)
    {
        if ($user->hasGlobalRead()) {
            return $query;
        }

        return $query->join('bill_perms', 'bill_perms.bill_id', 'bills.bill_id')
            ->where('bill_perms.user_id', $user->user_id);
    }

    // ---- Define Relationships ----

    public function ports()
    {
        return $this->belongsToMany(\App\Models\Port::class, 'bill_ports', 'bill_id', 'bill_id');
    }
}

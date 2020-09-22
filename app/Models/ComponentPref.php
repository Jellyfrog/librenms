<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * App\Models\ComponentPref
 *
 * @property int $id ID for each entry
 * @property int $component id from the component table
 * @property string $attribute Attribute for the Component
 * @property string $value Value for the Component
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\ComponentPref newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\ComponentPref newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\ComponentPref query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\ComponentPref whereAttribute($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\ComponentPref whereComponent($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\ComponentPref whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\ComponentPref whereValue($value)
 * @mixin \Eloquent
 */
class ComponentPref extends Model
{
    public $timestamps = false;
    protected $fillable = ['component', 'attribute', 'value'];

    public function setValueAttribute($value)
    {
        $this->attributes['value'] = is_array($value) ? json_encode($value) : (string) $value;
    }
}

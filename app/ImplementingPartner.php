<?php namespace App;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
class ImplementingPartner extends Model
{
	public $fillable = ['name', 'agency_id'];
	use SoftDeletes;
	protected $dates = ['deleted_at'];

    public function agency()
    {
      return $this->belongsTo('App\Agency');
    }

    public function counties()
    {
      return $this->belongsToMany('App\County');
    }
}
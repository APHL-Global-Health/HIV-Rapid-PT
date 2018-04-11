<?php namespace App;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
class Agency extends Model
{
	public $fillable = ['name'];
	use SoftDeletes;
	protected $dates = ['deleted_at'];
}
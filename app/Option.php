<?php namespace App;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Log;
class Option extends Model
{
	public $fillable = ['title', 'description'];
  	/**
  	 * Enabling soft deletes for options.
  	 *
  	 */
  	use SoftDeletes;
  	protected $dates = ['deleted_at'];

  	/**
  	 * The database table used by the model.
  	 *
  	 * @var string
  	 */
  	protected $table = 'options';
	/**
	* Return Option ID given the title
	* @param $title the title of the option
	*/
	public static function idByTitle($title=NULL)
	{
		if($title!=NULL)
		{
			try 
			{
				$option = Option::where('title', $title)->orderBy('title', 'asc')->firstOrFail();
				return $option->id;
			} 
			catch (ModelNotFoundException $e) 
			{
				Log::error("The option ` $title ` does not exist:  ". $e->getMessage());
				//TODO: send email?
				return null;
			}
		}
		else
		{
			return null;
		}
	}

	public static function nameByID($id=NULL)
	{
		if($id!=NULL)
		{
			try 
			{
				$option = Option::where('id', $id)->firstOrFail();
				return $option->title;
			} 
			catch (ModelNotFoundException $e) 
			{
				Log::error("The option ` $title ` does not exist:  ". $e->getMessage());
				//TODO: send email?
				return null;
			}
		}
		else
		{
			return null;
		}
	}
}

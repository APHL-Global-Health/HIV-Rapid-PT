<?php namespace App;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
class County extends Model
{
  	/**
  	* Enabling soft deletes for counties.
  	*
  	*/
  	use SoftDeletes;
  	protected $dates = ['deleted_at'];

  	/**
  	* The database table used by the model.
  	*
  	* @var string
  	*/
  	protected $table = 'counties';
    /**
    * Relationship with sub-countis.
    *
    */
    public function subCounties()
    {
       return $this->hasMany('App\SubCounty');
    }
    /**
    * Get facilities for a county
    *
    */
    public function facilities()
    {
        $subs = $this->subCounties()->pluck('id')->toArray();
        return Facility::whereIn('sub_county_id', $subs);
    }    
    /**
    * Relationship with implementing partners.
    *
    */
    public function implementingPartners()
    {
       return $this->belongsToMany('App\ImplementingPartner');
    }
    /**
    * Get users for a county
    *
    */
    public function users()
    {
        $prole = Role::idByName('Participant');
        $fls = $this->facilities()->pluck('id')->toArray();
        $users = User::select('users.*')->join('role_user', 'users.id', '=', 'role_user.user_id')->where('role_id', $prole)->whereIn('tier', $fls);
        return $users;
    }
    /**
    * Get shipments for a county
    *
    */
    public function shipments()
    {
        return $this->hasMany('App\Shipment');
    }
    /**
    * Return the county ID given the name
    *
    */
    public static function idByName($name=NULL)
    {
        if($name!=NULL)
        {
            try 
            {
                $count = County::where('name', $name)->orderBy('name', 'asc')->count();
                if($count > 0)
                {
                    $county = County::where('name', $name)->orderBy('name', 'asc')->first();
                    return $county->id;
                }
                else
                {
                    return null;
                }
            } 
            catch (ModelNotFoundException $e) 
            {
                Log::error("The county ` $name ` does not exist:  ". $e->getMessage());
                //TODO: send email?
                return null;
            }
        }
        else
        {
            return null;
        }
    }        
    /**
    * Get results for a county
    *
    */
    public function results()
    {
        $users = $this->users()->pluck('id');
        $enrolments = Enrol::whereIn('user_id', $users)->pluck('id');
        $results = Pt::whereIn('enrolment_id', $enrolments);
        return $results;
    }
}
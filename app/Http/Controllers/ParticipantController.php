<?php

namespace App\Http\Controllers;
set_time_limit(0);
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Notifications\Notification;
use App\Http\Controllers\Controller;
use App\User;
use App\Role;
use App\Facility;
use App\SubCounty;
use App\County;
use App\Program;
use App\Round;
use App\SmsHandler;

use DB;
use Hash;
use Auth;
use Mail;
use App\Libraries\AfricasTalkingGateway as Bulk;
//  Carbon - for use with dates
use Jenssegers\Date\Date as Carbon;
use Excel;
use App;
use File;

//  Notification
use App\Notifications\WelcomeNote;
use App\Notifications\RegretNote;
class ParticipantController extends Controller
{

    public function manageParticipant()
    {
        return view('participant.index');
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $error = ['error' => 'No results found, please try with different keywords.'];
        $users = User::whereNotNull('uid')-> latest()->withTrashed()->paginate(5);
        if(Auth::user()->isCountyCoordinator())
        {
            $users = County::find(Auth::user()->ru()->tier)->users()->latest()->withTrashed()->paginate(5);
        }
        else if(Auth::user()->isSubCountyCoordinator())
        {
           $users = SubCounty::find(Auth::user()->ru()->tier)->users()->latest()->withTrashed()->paginate(5);
        }
        else if(Auth::user()->isFacilityInCharge())
        {
           $users = Facility::find(Auth::user()->ru()->tier)->users()->latest()->withTrashed()->paginate(5);
        }
        if($request->has('q')) 
        {
            $search = $request->get('q');
            $users = User::where('name', 'LIKE', "%{$search}%")->orWhere('uid', 'LIKE', "%{$search}%")->latest()->withTrashed()->paginate(5);
            if(Auth::user()->isCountyCoordinator())
            {
                $users = County::find(Auth::user()->ru()->tier)->users()->where('users.name', 'LIKE', "%{$search}%")->orWhere('uid', 'LIKE', "%{$search}%")->latest()->withTrashed()->paginate(5);
            }
            else if(Auth::user()->isSubCountyCoordinator())
            {
                $users = SubCounty::find(Auth::user()->ru()->tier)->users()->where('users.name', 'LIKE', "%{$search}%")->orWhere('uid', 'LIKE', "%{$search}%")->latest()->withTrashed()->paginate(5);
            }
            else if(Auth::user()->isFacilityInCharge())
            {
               $users = Facility::find(Auth::user()->ru()->tier)->users()->where('users.name', 'LIKE', "%{$search}%")->orWhere('uid', 'LIKE', "%{$search}%")->latest()->withTrashed()->paginate(5);
            }
        }
        if($request->has('filter')) 
        {
            $search = $request->get('q');
            $users = User::whereNotNull('sms_code')->latest()->withTrashed()->paginate(5);
            if(Auth::user()->isCountyCoordinator())
            {
                $users = County::find(Auth::user()->ru()->tier)->users()->whereNotNull('sms_code')->latest()->withTrashed()->paginate(5);
            }
            else if(Auth::user()->isSubCountyCoordinator())
            {
                $users = SubCounty::find(Auth::user()->ru()->tier)->users()->whereNotNull('sms_code')->latest()->withTrashed()->paginate(5);
            }
            else if(Auth::user()->isFacilityInCharge())
            {
               $users = Facility::find(Auth::user()->ru()->tier)->users()->whereNotNull('sms_code')->latest()->withTrashed()->paginate(5);
            }
        }
        foreach($users as $user)
        {
            if(!empty($user->ru()->tier))
            {
                $facility = Facility::find($user->ru()->tier);
                $user->facility = $user->ru()->tier;
                $user->program = $user->ru()->program_id;
                $user->sub_county = $facility->subCounty->id;
                $user->county = $facility->subCounty->county->id;

                $user->mfl = $facility->code;
                $user->fac = $facility->name;
                $user->prog = Program::find($user->ru()->program_id)->name;
                $user->sub = $facility->subCounty->name;
                $user->kaunti = $facility->subCounty->county->name;
                $user->des = $user->designation($user->ru()->designation);
                $user->gndr = $user->maleOrFemale((int)$user->gender);
                $user->designation = $user->ru()->designation;
            }
            else
            {
                $user->facility = '';
                $user->program = '';
            }
            !empty($user->ru())?$user->role = $user->ru()->role_id:$user->role = '';
            !empty($user->ru())?$user->rl = Role::find($user->ru()->role_id)->name:$user->rl = '';
        }
        $response = [
            'pagination' => [
                'total' => $users->total(),
                'per_page' => $users->perPage(),
                'current_page' => $users->currentPage(),
                'last_page' => $users->lastPage(),
                'from' => $users->firstItem(),
                'to' => $users->lastItem()
            ],
            'data' => $users
        ];

        return $users->count() > 0 ? response()->json($response) : $error;
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //dd($request);
        $this->validate($request, [
            'name' => 'required',
            'gender' => 'required',
            'phone' => 'required',
            'email' => 'required',
            'address' => 'required',
            'username' => 'required'
        ]);
        $request->merge(['password' => Hash::make(User::DEFAULT_PASSWORD)]);
        $create = User::create($request->all());
        if($request->role)
        {
            $role = $request->role;
            $tier = NULL;
            $program_id = NULL;
            if($role == Role::idByName("Partner"))
            {
                $tier = implode(", ", $request->jimbo);
            }
            else if($role == Role::idByName("County Coordinator"))
            {
                $tier = $request->county_id;
            }
            else if($role == Role::idByName("Sub-County Coordinator"))
            {
                $tier = $request->sub_id;
            }
            else if($role == Role::idByName("Participant"))
            {
                $tier = $request->facility_id;
                $program_id = $request->program_id;
            }
            else if($role == Role::idByName("Facility Incharge"))
            {
                $tier = $request->facility_id;
            }
            $ru = DB::table('role_user')->insert(["user_id" => $create->id, "role_id" => $role, "tier" => $tier, "program_id" => $program_id]);
        }
        return response()->json($create);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //dd($request->all());
        $user = User::find($id);
        $user->name = $request->name;
        $user->phone = $request->phone;
        $user->email = $request->email;
        $user->address = $request->address;
        try{
            $user->save();
            $role = $request->role;
            $tier = Facility::idByCode($request->mfl_code);
            $program_id = $request->program_id;
            $designation = $request->designation;
            DB::table('role_user')->where('user_id', $id)->where('role_id', $role)->delete();
            $ru = DB::table('role_user')->insert(["user_id" => $id, "role_id" => $role, "tier" => $tier, "program_id" => $program_id, "designation" => $designation]);
        }
        catch(Exception $e)
        {
            abort(404);
        }
        
        return response()->json($user);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $user = User::find($id);
        $message    = "Dear ".$user->name.", NPHL has disabled your account.";
        try 
        {
            $smsHandler = new SmsHandler();
            $smsHandler->sendMessage($user->phone, $message);
        }
        catch ( AfricasTalkingGatewayException $e )
        {
            echo "Encountered an error while sending: ".$e->getMessage();
        }
        $user->delete();
        return response()->json(['done']);
    }

    /**
     * enable soft deleted record.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function restore($id) 
    {
        $user = User::withTrashed()->where('id', $id)->restore();
        $user = User::find($id);
        $message    = "Dear ".$user->name.", NPHL has enabled your account.";
        try 
        {
            $smsHandler = new SmsHandler();
            $smsHandler->sendMessage($user->phone, $message);
        }
        catch ( AfricasTalkingGatewayException $e )
        {
            echo "Encountered an error while sending: ".$e->getMessage();
        }
        return response()->json(['done']);
    }
    /**
     * Function to return list of tester-ranges.
     *
     */
    public function ranges()
    {
        $ranges = [
            User::ZERO_TO_TWO => '0 - 2',
            User::THREE_TO_FIVE => '3 - 5',
            User::SIX_TO_EIGHT => '6 - 8',
            User::NINE => '9'
        ];
        $categories = [];
        foreach($ranges as $key => $value)
        {
            $categories[] = ['id' => $key, 'value' => $value];
        }
        return $categories;
    }
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function participant(Request $request)
    {
        $error = ['error' => 'No results found, please try with different keywords.'];
        $participant = Role::idByName('Participant');
        $users = [];
        if($request->has('q')) 
        {
            $search = $request->get('q');
            $users = User::join('role_user', 'users.id', '=', 'role_user.user_id')->where('role_id', $participant)
                        ->where('name', 'LIKE', "%{$search}%")->orWhere('uid', 'LIKE', "%{$search}%")
                        ->orWhere('phone', 'LIKE', "%{$search}%")->latest()->paginate(5);
        }
        $response = [
            'pagination' => [
                'total' => $users->total(),
                'per_page' => $users->perPage(),
                'current_page' => $users->currentPage(),
                'last_page' => $users->lastPage(),
                'from' => $users->firstItem(),
                'to' => $users->lastItem()
            ],
            'data' => $users
        ];

        return $users->count() > 0 ? response()->json($response) : $error;
    }
    /**
     * Transfer the specified participant.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function transfer(Request $request, $id)
    {
        $this->validate($request, [
            'facility_id' => 'required',
            'program_id' => 'required',
        ]);
        $tier = Tier::where('user_id', $id)->first();
        $prog = NULL;
        $fac = NULL;
        if($request->facility_id)
        {
            $fac = $request->facility_id;
            $tier->tier = $fac;
        }
        if($request->program_id)
        {
            $prog = $request->program_id;
            $tier->program_id = $prog;
        }
        $response = $tier->save();

        return response()->json($response);
    }
    /**
     * Function for enrolling users to a round of testing
     *
     * @return \Illuminate\Http\Response
     */
    public function forEnrol(Request $request)
    {
        $error = ['error' => 'No results found, please try with different keywords.'];
        $role_id = Role::idByName('Participant');
        $ids = DB::table('role_user')->where('role_id', $role_id)->pluck('user_id');
        $usrs = User::whereIn('id', $ids)->whereNotNull('uid')->latest()->paginate(5);
        if($request->has('q')) 
        {
            $search = $request->get('q');
            $usrs = User::whereIn('id', $ids)->where('name', 'LIKE', "%{$search}%")->orWhere('uid', 'LIKE', "%{$search}%")->latest()->paginate(5);
        }
        if(count($usrs)>0)
        {
            foreach($usrs as $user)
            {
                //dd($user->ru());
                $user->facility = Facility::find($user->ru()->tier)->name;
                $user->program = Program::find($user->ru()->program_id)->name;
            }
        }
        $response = [
            'pagination' => [
                'total' => $usrs->total(),
                'per_page' => $usrs->perPage(),
                'current_page' => $usrs->currentPage(),
                'last_page' => $usrs->lastPage(),
                'from' => $usrs->firstItem(),
                'to' => $usrs->lastItem()
            ],
            'data' => $usrs
        ];

        return !empty($usrs) ? response()->json($response) : $error;
    }
    /**
     * Get enrolled user(s).
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function enrolled($id)
    {
        $error = ['error' => 'No results found, please try with different keywords.'];
        $ids = Round::find($id)->enrolments->pluck('user_id')->toArray();
        $usrs = User::whereIn('id', $ids)->latest()->paginate(5);
        //dd($usrs);
        if(count($usrs)>0)
        {
            foreach($usrs as $enrol)
            {
                $facility = Facility::find($enrol->ru()->tier);
                $enrol->facility = $facility->name;
                $enrol->mfl = $facility->code;
                $enrol->program = Program::find($enrol->ru()->program_id)->name;
            }
        }
        $response = [
            'pagination' => [
                'total' => $usrs->total(),
                'per_page' => $usrs->perPage(),
                'current_page' => $usrs->currentPage(),
                'last_page' => $usrs->lastPage(),
                'from' => $usrs->firstItem(),
                'to' => $usrs->lastItem()
            ],
            'data' => $usrs
        ];
        return !empty($usrs) ? response()->json($response) : $error;
    }
    /**
     * Function to return list of sexes.
     *
     */
    public function sex()
    {
        $sexes = [
            User::MALE => 'Male',
            User::FEMALE => 'Female'
        ];
        $categories = [];
        foreach($sexes as $key => $value)
        {
            $categories[] = ['title' => $value, 'name' => $key];
        }
        return $categories;
    }
    /**
     * Function to return list of designations.
     *
     */
    public function designations()
    {
        $designations = [
            0 => '',
            User::NURSE => 'Nurse',
            User::LABTECH => 'Lab Tech.',
            User::COUNSELLOR => 'Counsellor',
            User::RCO => 'RCO',
        ];
        $categories = [];
        foreach($designations as $key => $value)
        {
            $categories[] = ['title' => $value, 'name' => $key];
        }
        return $categories;
    }

    /**
     * Function to register new participants
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function register(Request $request)
    {
        $now = Carbon::now('Africa/Nairobi');
        //  Prepare to save user details
        //  Check if user exists
        $userId = User::idByName($request->name);
        if(!$userId)
            $userId = User::idByEmail($request->email);
        if(!$userId)
        {
            $user = new User;
            $user->name = $request->name;
            $user->gender = $request->gender;
            $user->email = $request->email;
            $user->phone = $request->phone;
            $user->address = $request->address;
            $user->designation = $request->designation;
            $user->username = $request->name;
            $user->deleted_at = $now;
            $user->save();
            $userId = $user->id;
        }
        //  Prepare to save facility details
        $facilityId = Facility::idByCode($request->mfl_code);
        if(!$facilityId)
            $facilityId = Facility::idByName($request->facility);
        if($facilityId)
            $facility = Facility::find($facilityId);
        else
            $facility = new Facility;
        $facility->code = $request->mfl_code;
        $facility->name = $request->facility;
        $facility->in_charge = $request->in_charge;
        $facility->in_charge_phone = $request->in_charge_phone;
        $facility->in_charge_email = $request->in_charge_email;
        //  Get sub-county
        $sub_county = SubCounty::idByName($request->sub_county);
        if(!$sub_county)
        {
            $sb = new SubCounty;
            $sb->name = $request->sub_county;
            $sb->county_id = $request->county;
            $sb->save();
            $sub_county = $sb->id;
        }
        $facility->sub_county_id = $sub_county;
        $facility->save();
        $facilityId = $facility->id;
        //  Prepare to save role-user details
        $roleId = Role::idByName('Participant');
        DB::table('role_user')->insert(['user_id' => $userId, 'role_id' => $roleId, 'tier' => $facilityId, 'program_id' => $request->program]);
        
        $token = mt_rand(100000, 999999);
        $user->sms_code = $token;
        $user->save();
        $message    = "Your Verification Code is: ".$token;
        try 
        {
            $smsHandler = new SmsHandler();
            $smsHandler->sendMessage($user->phone, $message);
        }
        catch ( AfricasTalkingGatewayException $e )
        {
            echo "Encountered an error while sending: ".$e->getMessage();
        }
        //  Do Email verification for email address
        $user->email_verification_code = Str::random(60);
        $user->save();
        $user->notify(new SendVerificationCode($user));
        /*$usr = $user->toArray();

        Mail::send('auth.verification', $usr, function($message) use ($usr) {
            $message->to($usr['email']);
            $message->subject('National HIV PT - Email Verification Code');
        });*/

        return response()->json(['phone' => $user->phone]);        
    }
    /**
     * Import the data in the worksheet
     *
     */
    public function importUserList(Request $request)
    {
        $exploded = explode(',', $request->list);
        $decoded = base64_decode($exploded[1]);
        if(str_contains($exploded[0], 'sheet'))
            $extension = 'xlsx';
        else
            $extension = 'xls';
        $fileName = uniqid().'.'.$extension;
        $county = County::find(1)->name;    // Remember to change this
        $folder = '/uploads/participants/';
        if(!is_dir(public_path().$folder))
            File::makeDirectory(public_path().$folder, 0777, true);
        file_put_contents(public_path().$folder.$fileName, $decoded);
        // dd();
        //  Handle the import
        //  Get the results
        //  Import a user provided file
        //  Convert file to csv
        $data = Excel::load('public/uploads/participants/'.$fileName, function($reader) {})->get();

        if(!empty($data) && $data->count())
        {
            foreach ($data->toArray() as $key => $value) 
            {
                if(!empty($value))
                {
                    $tname = NULL;
                    $tuid = NULL;
                    $tprogram = NULL;
                    $tphone = NULL;
                    $tfacility = NULL;
                    $temail = NULL;
                    foreach ($value as $mike => $ross) 
                    {
                        if(strcmp($mike, "tester_name") === 0)
                            $tname = $ross;
                        if(strcmp($mike, "bar_code") === 0)
                            $tuid = $ross;
                        if(strcmp($mike, "program") === 0)
                            $tprogram = $ross;
                        if(strcmp($mike, "testerphone") === 0)
                            $tphone = $ross;
                        if(strcmp($mike, "facility_name") === 0)
                            $tfacility = $ross;
                        if(strcmp($mike, "email") === 0)
                            $temail = $ross;
                    }
                    if(count($tphone) != 0)
                    {
                        $tphone = ltrim($tphone, '0');
                        $tphone = "+254".$tphone;
                        $tphone = trim($tphone);
                    }
                    if(!$tuid)
                        $tuid = uniqid();
                    $facility_id = Facility::idByName(trim($tfacility));
                    $program_id = Program::idByTitle(trim($tprogram));
                    $role_id = Role::idByName('Participant');
                    //  Prepare to save participant details
                    //$tester_id = User::idByName($tname);
                    /*if(!$tester_id)
                        $tester_id = User::idByEmail($temail);*/
                    // if(!$tester_id)
                    // {
                    $tester = new User;
                    $tester->password = Hash::make(User::DEFAULT_PASSWORD);
                    /*}
                    else
                        $tester = User::find($tester_id);*/
                    $tester->name = $tname;
                    $tester->gender = User::MALE;
                    $tester->email = $temail;
                    $tester->phone = $tphone;
                    $tester->username = $tuid;
                    $tester->uid = $tuid;
                    $tester->save();
                    //  prepare to save role-user
                    $ru = DB::table('role_user')->where('user_id', $tester->id)->where('role_id', $role_id)->count();
                    if($ru == 0)
                    {
                        DB::table('role_user')->insert(["user_id" => $tester->id, "role_id" => $role_id, "tier" => $facility_id, "program_id" => $program_id]);
                    }
                }
            }
        }
    }
    /**
     * Batch registration
     *
     */
    /*public function batchRegistration(Request $request)
    {
        $exploded = explode(',', $request->excel);
        $decoded = base64_decode($exploded[1]);
        if(str_contains($exploded[0], 'sheet'))
            $extension = 'xlsx';
        else
            $extension = 'xls';
        $fileName = uniqid().'.'.$extension;
        $county = 0;
        if(Auth::user()->isCountyCoordinator())
        {
            $county = County::find(Auth::user()->ru()->tier)->name;
            $folder = '/batch/registration/'.$county.'/';
        }
        else
            $folder = '/batch/registration/nphls/';
        if(!is_dir(public_path().$folder))
            File::makeDirectory(public_path().$folder, 0777, true);
        file_put_contents(public_path().$folder.$fileName, $decoded);
        // dd();
        //  Handle the import
        //  Get the results
        //  Import a user provided file
        //  Convert file to csv
        if(Auth::user()->isCountyCoordinator())
            $data = Excel::load('public/batch/registration/'.$county.'/'.$fileName, function($reader) {$reader->ignoreEmpty();})->get();
        else
            $data = Excel::load('public/batch/registration/nphls/'.$fileName, function($reader) {$reader->ignoreEmpty();})->get();
        if(!empty($data) && $data->count())
        {

            foreach ($data->toArray() as $key => $value) 
            {
                foreach($value as $harvey => $specter)
                {
                    if(!empty($specter))
                    {
                        $county = NULL;
                        $sub_county = NULL;
                        $facility = NULL;
                        $mfl = NULL;
                        $tfname = NULL;
                        $tsname = NULL;
                        $toname = NULL;
                        $tgender = NULL;
                        $tphone = NULL;
                        $temail = NULL;
                        $taddress = NULL;
                        $tdes = NULL;
                        $tprog = NULL;
                        $incharge = NULL;
                        $iphone = NULL;
                        $iemail = NULL;
                        foreach ($specter as $mike => $ross) 
                        {
                            if(strcmp($mike, "county") === 0)
                                $county = $ross;
                            if(strcmp($mike, "sub_county") === 0)
                                $sub_county = $ross;
                            if(strcmp($mike, "facility") === 0)
                                $facility = $ross;
                            if(strcmp($mike, "mfl_code") === 0)
                                $mfl = $ross;
                            if(strcmp($mike, "tester_first_name") === 0)
                                $tfname = $ross;
                            if(strcmp($mike, "tester_surname") === 0)
                                $tsname = $ross;
                            if(strcmp($mike, "tester_other_name") === 0)
                                $toname = $ross;
                            if(strcmp($mike, "gender") === 0)
                                $tgender = $ross;
                            if(strcmp($mike, "tester_mobile_number") === 0)
                                $tphone = $ross;
                            if(strcmp($mike, "tester_email") === 0)
                                $temail = $ross;
                            if(strcmp($mike, "tester_address") === 0)
                                $taddress = $ross;
                            if(strcmp($mike, "designation") === 0)
                                $tdes = $ross;
                            if(strcmp($mike, "program") === 0)
                                $tprog = $ross;
                            if(strcmp($mike, "in_charge") === 0)
                                $incharge = $ross;
                            if(strcmp($mike, "in_charge_email") === 0)
                                $iemail = $ross;
                            if(strcmp($mike, "in_charge_phone") === 0)
                                $iphone = $ross;
                        }
                        //  clean phone
                        if(!empty($tphone))
                        {
                            $tphone = ltrim($tphone, '0');
                            $tphone = "+254".$tphone;
                            $tphone = trim($tphone);
                        }
                        //  process gender
                        if(strcmp($tgender, "Male") === 0)
                            $tgender = User::MALE;
                        else
                            $tgender = User::FEMALE;
                        //  process designation
                        if(strcmp($tdes, "Nurse") === 0)
                            $tdes = User::NURSE;
                        else if(strcmp($tdes, "Lab Tech.") === 0)
                            $tdes = User::LABTECH;
                        else if(strcmp($tdes, "Counsellor") === 0)
                            $tdes = User::COUNSELLOR;
                        else if(strcmp($tdes, "RCO") === 0)
                            $tdes = User::RCO;

                        //  process user details only if the name exists
                        if($tfname)
                        {
                            $userId = User::idByName($tsname." ".$tfname." ".$toname);
                            if(!$userId)
                                $userId = User::idByEmail($temail);
                            if(!$userId)
                            {
                                $user = new User;
                                $user->name = $tsname." ".$tfname." ".$toname;
                                $user->gender = $tgender;
                                $user->email = $temail;
                                $user->phone = $tphone;
                                $user->address = $taddress;
                                $user->username = uniqid();
                                $user->save();
                                $user->username = $user->id;
                                $user->password = User::DEFAULT_PASSWORD;
                                $user->save();
                                $userId = $user->id;
                            }
                            //  Prepare to save facility details
                            $facilityId = Facility::idByCode($mfl);
                            if(!$facilityId)
                                $facilityId = Facility::idByName(trim($facility));
                            if($facilityId)
                                $fc = Facility::find($facilityId);
                            else
                                $fc = new Facility;
                            $fc->code = $mfl;
                            $fc->name = $facility;
                            $fc->in_charge = $incharge;
                            $fc->in_charge_phone = $iphone;
                            $fc->in_charge_email = $iemail;
                            //  Get sub-county
                            $sub_county_id = SubCounty::idByName($sub_county);
                            if(!$sub_county_id)
                            {
                                $sb = new SubCounty;
                                $sb->name = $sub_county;
                                $sb->county_id = County::idByName($county);
                                $sb->save();
                                $sub_county_id = $sb->id;
                            }
                            $fc->sub_county_id = $sub_county_id;
                            $fc->save();
                            $facilityId = $fc->id;
                            //  Prepare to save role-user details
                            $roleId = Role::idByName('Participant');
                            DB::table('role_user')->insert(['user_id' => $userId, 'role_id' => $roleId, 'tier' => $facilityId, 'program_id' => Program::idByTitle($tprog), "designation" => $tdes]);
                            //  send email and sms for registration
                        }
                    }
                }
            }
        }
    }*/
    /**
     * Check for user phone verification code
     *
     * @param  array  $data
     * @return User
     */
    public function phoneVerification(Request $request)
    {
        $token = $request->code;
        // dd($token);
        $check = User::where("sms_code", $token)->withTrashed()->first();
        
        if(!is_null($check)){
            $user = User::withTrashed()->find($check->id);

            if($user->phone_verified == 1){
                return response()->json(["info" => "Your phone number is already verified."]);
            }

            $user->phone_verified = 1;
            $user->save();

            return response()->json(["success" => "Phone number successfully verified. Your ID will be sent to you shortly."]);
        }
        return response()->json(["warning" => "Your token is invalid."]);
    }
    /**
     * Check for user Activation Code
     *
     * @param  array  $data
     * @return User
     */
    public function emailVerification($token)
    {
        $check = User::where('email_verification_code', $token)->first();

        if(!is_null($check)){
            $user = User::find($check->id);

            if($user->email_verified == 1){
                return redirect()->to('login')
                    ->with('success', "Your email is already verified.");                
            }

            $user->update(['email_verified' => 1]);

            return redirect()->to('login')
                ->with('success', "Email successfully verified.");
        }
        return redirect()->to('login')
                ->with('warning', "Your token is invalid.");
    }
    /**
     *   Function to approve participant
     */
    public function approve(Request $request)
    {
        $userId = $request->id;
        $user = User::withTrashed()->where('id', $userId);
        //  Assign UID and restore
        $user->restore();
        $user = User::find($userId);
        $max = $user->id; //change this to pick sequential unique ids
        $user->uid = $max;
        $user->username = $max;
        $user->save();

        //send mail
        $token = app('auth.password.broker')->createToken($user);
        $user->token = $token;
        $user->notify(new WelcomeNote($user));
        
        //  Bulk-sms settings
        $api = DB::table('bulk_sms_settings')->first();
        $username   = $api->code;
        $apikey     = $api->api_key;
        //  Remove beginning 0 and append +254
        $phone = ltrim($user->phone, '0');
        $recipient = "+254".$phone;
        $message    = "Dear ".$user->name.", NPHL has approved your request to participate in PT. Your tester ID is ".$user->uid.". Use the link sent to your email to get started.";
        // Create a new instance of our awesome gateway class
        $gateway    = new Bulk($username, $apikey);
       
        try 
        {
            $smsHandler = new SmsHandler();
            $smsHandler->sendMessage($user->phone, $message);
        }
        catch ( AfricasTalkingGatewayException $e )
        {
            echo "Encountered an error while sending: ".$e->getMessage();
        }
    }
    public function denyUserVerification(Request $request, $id){
       
        $user = User::withTrashed()->find($id);
        $user->status = User::REJECTED;
        $user->reason = $request->reason;
        $now = Carbon::now('Africa/Nairobi')->toDateString();
        $user->status_date = $now;
        $user->save();
        $user->delete();
        
        $message = "Dear ".$user->name.", NPHL has rejected your request to participate in PT because ".$request->reason;
        return response()->json($message);
        
        /*$user->notify(new RegretNote($user));
        try 
        {
            $smsHandler = new SmsHandler();
            $smsHandler->sendMessage($user->phone, $message);
        }
        catch ( AfricasTalkingGatewayException $e )
        {
            echo "Encountered an error while sending: ".$e->getMessage();
        }*/
    }

    /**
     * Function to download all participants - batch + self-enrolled
     *
     */
    public function testerSummary()
    {
        // $data = Program::get()->toArray();
        $suffix = "PARTICIPANTS SUMMARY";
        $title = "";
        $users = NULL;
        $roleId = Role::idByName('Participant');
        $counter = 0;
        //  workbook title
        if(Auth::user()->isCountyCoordinator())
        {
            $title = County::find(Auth::user()->ru()->tier)->name." COUNTY ".$suffix;
            $counter = County::find(Auth::user()->ru()->tier)->users->count();
        }
        else
        {
            $title = "KENYA RAPID HIV PT ".$suffix;
            $counter = DB::table('role_user')->where('role_id', $roleId)->count();
        }
        if($counter > 0)
        {
            return Excel::create($title, function($excel) use ($users, $roleId) 
            {
                if(Auth::user()->isCountyCoordinator())
                {
                    $countyId = Auth::user()->ru()->tier;
                    $county = County::find($countyId)->name;
                    //  sub-counties and facilities
                    $fIds = County::find($countyId)->facilities()->pluck('id');
                    $ids = DB::table('role_user')->where('role_id', $roleId)->whereIn('tier', $fIds)->pluck('user_id')->toArray();
                    $testers = $ids;
                    $testers = implode(",", $testers);

                    $summary = [];

                    if (empty($testers)) {
                       $summary[] = ['TESTER NAME' => '', 'TESTER UNIQUE ID' => '', 'TESTER PHONE' => '', 'TESTER EMAIL' => '', 'PROGRAM' => '', 'DESIGNATION' => '', 'FACILITY' => '', 'MFL CODE' => '', 'IN CHARGE' => '', 'IN CHARGE PHONE' => '', 'IN CHARGE EMAIL' => '']; 
                    }else{
                        $data = DB::select("SELECT u.name AS 'TESTER NAME', u.uid AS 'TESTER UNIQUE ID', u.phone AS 'TESTER PHONE', u.email AS 'TESTER EMAIL', p.name AS 'PROGRAM', ru.designation AS 'DESIGNATION', f.name AS 'FACILITY', f.code AS 'MFL CODE', f.in_charge AS 'IN CHARGE', f.in_charge_phone AS 'IN CHARGE PHONE', f.in_charge_email AS 'IN CHARGE EMAIL' FROM users u, facilities f, role_user ru, programs p WHERE u.id = ru.user_id AND ru.tier = f.id AND ru.program_id = p.id AND u.id IN (".$testers.") ORDER BY u.uid ASC;");
                        
                        foreach($data as $key => $value)
                        {
                            $tname = NULL;
                            $tuid = NULL;
                            $tname = NULL;
                            $tphone = NULL;
                            $temail = NULL;
                            $tprog = NULL;
                            $tdes = NULL;
                            $facility = NULL;
                            $mfl = NULL;
                            $icharge = NULL;
                            $iphone = NULL;
                            $iemail = NULL;
                            foreach($value as $mike => $ross)
                            {
                                if(strcasecmp("TESTER NAME", $mike) == 0)
                                    $tname = $ross;
                                if(strcasecmp("TESTER UNIQUE ID", $mike) == 0)
                                    $tuid = $ross;
                                if(strcasecmp("TESTER PHONE", $mike) == 0)
                                    $tphone = $ross;
                                if(strcasecmp("TESTER EMAIL", $mike) == 0)
                                    $temail = $ross;
                                if(strcasecmp("PROGRAM", $mike) == 0)
                                    $tprog = $ross;
                                if(strcasecmp("DESIGNATION", $mike) == 0)
                                    $tdes = User::des($ross);
                                if(strcasecmp("FACILITY", $mike) == 0)
                                    $facility = $ross;
                                if(strcasecmp("MFL CODE", $mike) == 0)
                                    $mfl = $ross;
                                if(strcasecmp("IN CHARGE", $mike) == 0)
                                    $icharge = $ross;
                                if(strcasecmp("IN CHARGE PHONE", $mike) == 0)
                                    $iphone = $ross;
                                if(strcasecmp("IN CHARGE EMAIL", $mike) == 0)
                                    $iemail = $ross;
                            }
                            $summary[] = ['TESTER NAME' => $tname, 'TESTER UNIQUE ID' => $tuid, 'TESTER PHONE' => $tphone, 'TESTER EMAIL' => $temail, 'PROGRAM' => $tprog, 'DESIGNATION' => $tdes, 'FACILITY' => $facility, 'MFL CODE' => $mfl, 'IN CHARGE' => $icharge, 'IN CHARGE PHONE' => $iphone, 'IN CHARGE EMAIL' => $iemail];                   
                        }
                    }
                    $excel->sheet($sheetTitle, function($sheet) use ($summary) {
                        $sheet->fromArray($summary);
                    });
                }
                else
                {
                    $counties = County::all();
                    foreach($counties as $county)
                    {
                        $sheetTitle = $county->name;
                        $fIds = $county->facilities()->pluck('id');
                        $ids = DB::table('role_user')->where('role_id', $roleId)->whereIn('tier', $fIds)->pluck('user_id')->toArray();
                        $testers = $ids;
                   
                        $testers = implode(",", $testers);

                        if (empty($testers)) {
                           $summary[] = ['TESTER NAME' => '', 'TESTER UNIQUE ID' => '', 'TESTER PHONE' => '', 'TESTER EMAIL' => '', 'PROGRAM' => '', 'DESIGNATION' => '', 'FACILITY' => '', 'MFL CODE' => '', 'IN CHARGE' => '', 'IN CHARGE PHONE' => '', 'IN CHARGE EMAIL' => '']; 
                        }else{
                            $data = DB::select("SELECT u.name AS 'TESTER NAME', u.uid AS 'TESTER UNIQUE ID', u.phone AS 'TESTER PHONE', u.email AS 'TESTER EMAIL', p.name AS 'PROGRAM', ru.designation AS 'DESIGNATION', f.name AS 'FACILITY', f.code AS 'MFL CODE', f.in_charge AS 'IN CHARGE', f.in_charge_phone AS 'IN CHARGE PHONE', f.in_charge_email AS 'IN CHARGE EMAIL' FROM users u, facilities f, role_user ru, programs p WHERE u.id = ru.user_id AND ru.tier = f.id AND ru.program_id = p.id AND u.id IN (".$testers.") ORDER BY u.uid ASC;");
                            // dd($data);
                            //  create assotiative array
                            $summary = [];
                            foreach($data as $key => $value)
                            {
                                $tname = NULL;
                                $tuid = NULL;
                                $tname = NULL;
                                $tphone = NULL;
                                $temail = NULL;
                                $tprog = NULL;
                                $tdes = NULL;
                                $facility = NULL;
                                $mfl = NULL;
                                $icharge = NULL;
                                $iphone = NULL;
                                $iemail = NULL;
                                foreach($value as $mike => $ross)
                                {
                                    if(strcasecmp("TESTER NAME", $mike) == 0)
                                        $tname = $ross;
                                    if(strcasecmp("TESTER UNIQUE ID", $mike) == 0)
                                        $tuid = $ross;
                                    if(strcasecmp("TESTER PHONE", $mike) == 0)
                                        $tphone = $ross;
                                    if(strcasecmp("TESTER EMAIL", $mike) == 0)
                                        $temail = $ross;
                                    if(strcasecmp("PROGRAM", $mike) == 0)
                                        $tprog = $ross;
                                    if(strcasecmp("DESIGNATION", $mike) == 0)
                                        $tdes = User::des($ross);
                                    if(strcasecmp("FACILITY", $mike) == 0)
                                        $facility = $ross;
                                    if(strcasecmp("MFL CODE", $mike) == 0)
                                        $mfl = $ross;
                                    if(strcasecmp("IN CHARGE", $mike) == 0)
                                        $icharge = $ross;
                                    if(strcasecmp("IN CHARGE PHONE", $mike) == 0)
                                        $iphone = $ross;
                                    if(strcasecmp("IN CHARGE EMAIL", $mike) == 0)
                                        $iemail = $ross;
                                }
                                $summary[] = ['TESTER NAME' => $tname, 'TESTER UNIQUE ID' => $tuid, 'TESTER PHONE' => $tphone, 'TESTER EMAIL' => $temail, 'PROGRAM' => $tprog, 'DESIGNATION' => $tdes, 'FACILITY' => $facility, 'MFL CODE' => $mfl, 'IN CHARGE' => $icharge, 'IN CHARGE PHONE' => $iphone, 'IN CHARGE EMAIL' => $iemail];                   
                            }
                        }
                        $excel->sheet($sheetTitle, function($sheet) use ($summary) {
                            $sheet->fromArray($summary);
                        });
                    }
                }
            })->download('xlsx');
        }
        else
        {
            return redirect()->back()->with('error', 'No data for PT participants found');
        }
    }
}
$excel = App::make('excel');
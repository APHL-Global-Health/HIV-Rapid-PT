<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Round;
use App\Enrol;
use App\Program;
use App\Facility;
use App\Notification;
use App\User;
use App\County;
use App\Role;
use App\SmsHandler;
use App\SubCounty;
use App\Designation;

use App\Libraries\AfricasTalkingGateway as Bulk;

use Auth;
use DB;
use Jenssegers\Date\Date as Carbon;
use Excel;
use App;
use File;
use Hash;
//  Notification
use App\Notifications\WelcomeNote;
use App\Notifications\EnrollmentNote;

class RoundController extends Controller
{

    public function manageRound()
    {
        return view('round.index');
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $error = ['error' => 'No results found, please try with different keywords.'];
        $rounds = Round::latest()->withTrashed()->paginate(5);
        if($request->has('q')) 
        {
            $search = $request->get('q');
            $rounds = Round::where('name', 'LIKE', "%{$search}%")->latest()->withTrashed()->paginate(5);
        }

        $response = [
            'pagination' => [
                'total' => $rounds->total(),
                'per_page' => $rounds->perPage(),
                'current_page' => $rounds->currentPage(),
                'last_page' => $rounds->lastPage(),
                'from' => $rounds->firstItem(),
                'to' => $rounds->lastItem()
            ],
            'data' => $rounds
        ];

        return $rounds->count() > 0 ? response()->json($response) : $error;
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {      
        $rounds = Round::where('name', 'LIKE', "{$request->name}")->withTrashed()->get();

        if ($rounds->count() > 0) {

            return response()->json('1');

        }else if ($request->start_date > $request->end_date) {
           
            return response()->json('2');

        }else
        {        

            $request->request->add(['user_id' => Auth::user()->id]);

            $create = Round::create($request->all());

            return response()->json($create);
        }
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
        $request->request->add(['user_id' => Auth::user()->id]);

        $edit = Round::find($id)->update($request->all());

        return response()->json($edit);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        Round::find($id)->delete();
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
        $round = Round::withTrashed()->find($id)->restore();
        return response()->json(['done']);
    }
    /**
     * Function to return list of rounds.
     *
     */
    public function rounds()
    {
        $rounds = Round::pluck('description', 'id');
        $categories = [];
        foreach($rounds as $key => $value)
        {
            $categories[] = ['id' => $key, 'value' => $value];
        }
        return $categories;
    }
    /**
     * Function to return list of rounds already done by a user.
     *
     */
    public function roundsDone()
    {
        // get enrolments with no submissions
        $ids = Auth::user()->enrol()->where('status', 0)->pluck('round_id');
        // fetch rounds details 
        $rounds = Round::whereIn('id', $ids)->pluck('description', 'id');
        // format to match dropdown values
        // dd($rounds);
        $categories = [];
        foreach($rounds as $key => $value)
        {
            $categories[] = ['id' => $key, 'value' => $value];
        }
        return $categories;
    }
    /**
     * Enrol a user(s).
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function enrol(Request $request)
    {
        $roundId = $request->round_id;
        $phone_numbers = [];
        foreach($request->usrs as $key => $value)
        {
            $enrol = new Enrol;
            $enrol->user_id = (int)$value;
            $enrol->round_id = $roundId;
            $enrol->save();
            $user = User::find($enrol->user_id);
            if($user->phone)
            {
                array_push($phone_numbers, $user->phone);
            }
        }
        $recipients = NULL;
        $recipients = implode(",", $phone_numbers);
        //  Send SMS
        $round = Round::find($roundId)->name;
        $message = Notification::where('template', Notification::ENROLMENT)->first()->message;
        $message = ApiController::replace_between($message, '[', ']', $round);
        $message = str_replace(' [', ' ', $message);
        $message = str_replace('] ', ' ', $message);
        //  Bulk-sms settings
        $api = DB::table('bulk_sms_settings')->first();
        $username   = $api->username;
        $apikey     = $api->api_key;
        if($recipients)
        {
            // Specified sender-id
            $from = $api->code;
            // Create a new instance of Bulk SMS gateway.
            $sms    = new Bulk($username, $apikey);
            // use try-catch to filter any errors.
            try
            {
            // Send messages
            $results = $sms->sendMessage($recipients, $message, $from);
            foreach($results as $result)
            {
                // status is either "Success" or "error message" and save.
                $number = $result->number;
                //  Save the results
                DB::table('broadcast')->insert(['number' => $number, 'bulk_id' => $bulk->id]);
            }
            }
            catch ( AfricasTalkingGatewayException $e )
            {
            echo "Encountered an error while sending: ".$e->getMessage();
            }
        }
        return response()->json('Enrolled.');
    }
    /**
     * Function to return possible durations of rounds.
     *
     */
    public function durations()
    {
        $response = [];
        $data = [
                    Round::ONE => "1 Month", 
                    Round::TWO => "2 Months", 
                    Round::THREE => "3 Months", 
                    Round::FOUR => "4 Months", 
                    Round::FIVE => "5 Months", 
                    Round::SIX => "6 Months",
                    Round::SEVEN => "7 Months", 
                    Round::EIGHT => "8 Months"
                ];
        foreach($data as $key => $value)
        {
            $response[] = ['id' => $key, 'value' => $value];
        }
        return $response;
    }
    /**
     * Function to enrol participants using excel sheet uploaded
     *
     */
    /*public function batchEnrolment(Request $request)
    {
        $rId = $request->id;
        $id = Round::find($rId)->name;
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
            $folder = '/batch/'.$id.'/enrolment/'.$county.'/';
        }
        else
            $folder = '/batch/'.$id.'/enrolment/nphls/';
        if(!is_dir(public_path().$folder))
            File::makeDirectory(public_path().$folder, 0777, true);
        file_put_contents(public_path().$folder.$fileName, $decoded);
        // dd();
        //  Handle the import
        //  Get the results
        //  Import a user provided file
        //  Convert file to csv
        if(Auth::user()->isCountyCoordinator())
            $data = Excel::load('public/batch/'.$id.'/enrolment/'.$county.'/'.$fileName, function($reader) {})->get();
        else
            $data = Excel::load('public/batch/'.$id.'/enrolment/nphls/'.$fileName, function($reader) {$reader->ignoreEmpty();})->get();
        if(!empty($data) && $data->count())
        {
            foreach ($data->toArray() as $key => $value) 
            {
                
                    // dd($value);
                    if(!empty($value))
                    {
                        $mfl = NULL;
                        $uid = NULL;
                        $tphone = NULL;
                        $temail = NULL;
                        $taddress = NULL;
                        $tdes = NULL;
                        $tprog = NULL;
                        $incharge = NULL;
                        $iphone = NULL;
                        $iemail = NULL;
                        foreach ($value as $mike => $ross) 
                        {
                            if(strcmp($mike, "mfl_code") === 0)
                                $mfl = $ross;
                            if(strcmp($mike, "tester_unique_id") === 0)
                                $uid = $ross;
                            if(strcmp($mike, "tester_phone") === 0)
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
                        //  Update user details where necessary
                        $user = User::find(User::idByUid($uid));
                        $user->phone = $tphone;
                        $user->email = $temail;
                        $user->save();                    
                        //  Update facility details where applicable
                        $facility = Facility::find(Facility::idByCode((int)$mfl));
                        $facility->in_charge = $incharge;
                        $facility->in_charge_email = $iemail;
                        $facility->in_charge_phone = $iphone;
                        $facility->save();
                        //  Update role-user
                        //  Enrol the participant to the pt round
                        $userId = $user->id;
                        $roundId = $rId;
                        $enrol = Enrol::where('round_id', $roundId)->where('user_id', $userId)->get();
                        if(count($enrol) == 0)
                        {
                            $enrol = new Enrol;
                            $enrol->round_id = $roundId;
                            $enrol->user_id = $userId;
                            $enrol->save();
                        }
                        //  Send SMS notification    
                        $phone = ltrim($user->phone, '0');
                        $recipients = "+254".trim($phone);          
                        $round = Round::find($roundId)->name;
                        $message = Notification::where('template', Notification::ENROLMENT)->first()->message;
                        $message = ApiController::replace_between($message, '[', ']', $round);
                        $message = str_replace(' [', ' ', $message);
                        $message = str_replace('] ', ' ', $message);
                        //  Bulk-sms settings
                        $api = DB::table('bulk_sms_settings')->first();
                        $username   = $api->code;
                        $apikey     = $api->api_key;
                        if($recipients)
                        {
                            // Specified sender-id
                            $from = $api->code;
                            // Create a new instance of Bulk SMS gateway.
                            $sms    = new Bulk($username, $apikey);
                            // use try-catch to filter any errors.
                            try
                            {
                            // Send messages
                            $results = $sms->sendMessage($recipients, $message, $from);
                            foreach($results as $result)
                            {
                                // status is either "Success" or "error message" and save.
                                $number = $result->number;
                                //  Save the results
                                DB::table('broadcast')->insert(['number' => $number, 'bulk_id' => $bulk->id]);
                            }
                            }
                            catch ( AfricasTalkingGatewayException $e )
                            {
                            echo "Encountered an error while sending: ".$e->getMessage();
                            }
                        }
                    }
                
            }
        }
    }
   */
     public function loadparticipants(Request $request)
    {
        $error = ['error' => 'No results found, please try with different keywords.'];
        $participants = User::whereNotNull('uid')-> latest()->withTrashed()->paginate(5);
        if(Auth::user()->isCountyCoordinator())
        {
            $participants = County::find(Auth::user()->ru()->tier)->users()->latest()->withTrashed()->paginate(5);
        }
        else if(Auth::user()->isSubCountyCoordinator())
        {
           $participants = SubCounty::find(Auth::user()->ru()->tier)->users()->latest()->withTrashed()->paginate(5);
        }
        else if(Auth::user()->isFacilityInCharge())
        {
           $participants = Facility::find(Auth::user()->ru()->tier)->users()->latest()->withTrashed()->paginate(5);
        }
        if($request->has('q')) 
        {
            $search = $request->get('q');
            $participants = User::where('name', 'LIKE', "%{$search}%")->orWhere('uid', 'LIKE', "%{$search}%")->latest()->withTrashed()->paginate(5);
            if(Auth::user()->isCountyCoordinator())
            {
                $participants = County::find(Auth::user()->ru()->tier)->users()->where('users.name', 'LIKE', "%{$search}%")->orWhere('uid', 'LIKE', "%{$search}%")->latest()->withTrashed()->paginate(5);
            }
            else if(Auth::user()->isSubCountyCoordinator())
            {
                $participants = SubCounty::find(Auth::user()->ru()->tier)->users()->where('users.name', 'LIKE', "%{$search}%")->orWhere('uid', 'LIKE', "%{$search}%")->latest()->withTrashed()->paginate(5);
            }
            else if(Auth::user()->isFacilityInCharge())
            {
               $participants = Facility::find(Auth::user()->ru()->tier)->users()->where('users.name', 'LIKE', "%{$search}%")->orWhere('uid', 'LIKE', "%{$search}%")->latest()->withTrashed()->paginate(5);
            }
        }
        if($request->has('filter')) 
        {
            $search = $request->get('q');
            $participants = User::whereNotNull('sms_code')->latest()->withTrashed()->paginate(5);
            if(Auth::user()->isCountyCoordinator())
            {
                $participants = County::find(Auth::user()->ru()->tier)->users()->whereNotNull('sms_code')->latest()->withTrashed()->paginate(5);
            }
            else if(Auth::user()->isSubCountyCoordinator())
            {
                $participants = SubCounty::find(Auth::user()->ru()->tier)->users()->whereNotNull('sms_code')->latest()->withTrashed()->paginate(5);
            }
            else if(Auth::user()->isFacilityInCharge())
            {
               $participants = Facility::find(Auth::user()->ru()->tier)->users()->whereNotNull('sms_code')->latest()->withTrashed()->paginate(5);
            }
        }
        foreach($participants as $participant)
        {
            if(!empty($participant->ru()->tier))
            {
                $facility = Facility::find($participant->ru()->tier);
                $participant->facility = $participant->ru()->tier;
                $participant->program = $participant->ru()->program_id;
                $participant->sub_county = $facility->subCounty->id;
                $participant->county = $facility->subCounty->county->id;

                $participant->mfl = $facility->code;
                $participant->fac = $facility->name;
                $participant->prog = Program::find($participant->ru()->program_id)->name;
                $participant->sub = $facility->subCounty->name;
                $participant->kaunti = $facility->subCounty->county->name;
                $participant->des = $participant->designation($participant->ru()->designation);
                $participant->gndr = $participant->maleOrFemale((int)$participant->gender);
                $participants->designation = $participant->ru()->designation;
            }
            else
            {
                $participant->facility = '';
                $participant->program = '';
            }
            !empty($participant->ru())?$participant->role = $participant->ru()->role_id:$participant->role = '';
            !empty($participant->ru())?$participant->rl = Role::find($participant->ru()->role_id)->name:$participant->rl = '';
        }
        $response = [
            'pagination' => [
                'total' => $participants->total(),
                'per_page' => $participants->perPage(),
                'current_page' => $participants->currentPage(),
                'last_page' => $participants->lastPage(),
                'from' => $participants->firstItem(),
                'to' => $participants->lastItem()
            ],
            'data' => $participants
        ];

        return $participants->count() > 0 ? response()->json($response) : $error;
           


    }
    /**
     * Function to download the participants in the past round
     *
     */
    public function testerSummary(Request $request)
    {
        $rId = $request->id;
        $roundId = Round::find($rId)->name;
        $suffix = "ROUND ".$roundId." PARTICIPANTS SUMMARY";
        $title = "";
        $users = NULL;
        $roleId = Role::idByName('Participant');        
        //check if anyone registered for that round is enrolled to tha round
        $p = Enrol::where('round_id',$rId)->get();
        if(count($p) == 0){
            Session()->flash ('message',"The round has no participants please add participants to the round");
            return back();
        }
        //  workbook title
        if(Auth::user()->isCountyCoordinator())
            $title = County::find(Auth::user()->ru()->tier)->name." COUNTY ".$suffix;
        else
            $title = "KENYA RAPID HIV PT ".$suffix;
        return Excel::create($title, function($excel) use ($rId, $roundId, $users, $roleId, $request) 
        {
            $round = Round::find($rId);
            if(Auth::user()->isCountyCoordinator())
            {
                $countyId = Auth::user()->ru()->tier;
                $county = County::find($countyId)->name;
                //  sub-counties and facilities
                $fIds = County::find($countyId)->facilities()->pluck('id');
                $ids = DB::table('role_user')->where('role_id', $roleId)->whereIn('tier', $fIds)->pluck('user_id');
                if($request->status)
                    $ids = User::whereIn('id', $ids)->whereBetween('date_registered', [$round->start_date, $round->end_date])->pluck('id');
                $testers = Enrol::where('round_id', $rId)->whereIn('user_id', $ids)->pluck('user_id')->toArray();
                $testers = implode(",", $testers);               
                $summary = [];
                $sheetTitle = $county;
                if (empty($testers)) {
                    $summary[] = [
                        'County' => '',
                        'Sub County' => '',
                        'Facility' => '',
                        'MFL Code' => '',
                        'Tester Enrollment ID' => '',
                        'Tester First Name' => '',
                        'Tester Surname' => '',
                        'Tester Other Name' => '',
                        'Gender' => '',
                        'Tester Mobile Number' => '',
                        'Tester Email' => '',
                        'Tester Address' => '',
                        'Designation' => '',
                        'Program' => '',
                        'In Charge' => '',
                        'In Charge Email' => '',
                        'In Charge Phone' => '',
                    ];
                }else{
                    $data = DB::select(
                        "SELECT
                        u.first_name AS 'Tester First Name',
                        u.last_name AS 'Tester Surname',
                        u.middle_name AS 'Tester Other Name',
                        u.uid AS 'Tester Enrollment ID',
                        u.gender AS 'Gender',
                        u.phone AS 'Tester Mobile Number',
                        u.email AS 'Tester Email',
                        u.address AS 'Tester Address',
                        p.name AS 'Program',
                        ru.designation AS 'Designation',
                        f.name AS 'Facility',
                        f.code AS 'MFL Code',
                        f.in_charge AS 'In Charge',
                        f.in_charge_phone AS 'In Charge Phone',
                        f.in_charge_email AS 'In Charge Email'
                        FROM users u, facilities f, role_user ru, programs p
                        WHERE u.id = ru.user_id
                            AND ru.program_id = p.id
                            AND ru.tier = f.id
                            AND ru.program_id = p.id
                            AND u.id
                        IN (".$testers.") ORDER BY u.uid ASC;");
                    
                    foreach($data as $key => $value)
                    {
                        $tfirst_name = NULL;
                        $tmiddle_name = NULL;
                        $tlast_name = NULL;
                        $tuid = NULL;
                        $tphone = NULL;
                        $temail = NULL;
                        $tprog = NULL;
                        $tdes = NULL;
                        $facility = NULL;
                        $mfl = NULL;
                        $icharge = NULL;
                        $iphone = NULL;
                        $iemail = NULL;
                        $tcounty = NULL;
                        $tsub_county = NULL;
                        $tgender = NULL;
                        $taddress = NULL;
                        foreach($value as $mike => $ross)
                        {
                            if(strcasecmp("County", $mike) == 0)
                                $tcounty = $ross;
                            if(strcasecmp("Sub County", $mike) == 0)
                                $tsub_county = $ross;
                            if(strcasecmp("Facility", $mike) == 0)
                                $facility = $ross;
                            if(strcasecmp("MFL Code", $mike) == 0)
                                $mfl = $ross;
                            if(strcasecmp("Tester Enrollment ID", $mike) == 0)
                                $tuid = $ross;
                            if(strcasecmp("Tester First Name", $mike) == 0)
                                $tfirst_name = $ross;
                            if(strcasecmp("Tester Surname", $mike) == 0)
                                $tlast_name = $ross;
                            if(strcasecmp("Tester Other Name", $mike) == 0)
                                $tmiddle_name = $ross;
                            if(strcasecmp("Gender", $mike) == 0)
                                $tgender = $ross;
                            if(strcasecmp("Tester Mobile Number", $mike) == 0)
                                $tphone = $ross;
                            if(strcasecmp("Tester Email", $mike) == 0)
                                $temail = $ross;
                            if(strcasecmp("Tester Address", $mike) == 0)
                                $taddress = $ross;
                            if(strcasecmp("Designation", $mike) == 0)
                                $tdes = $ross;
                            if(strcasecmp("Program", $mike) == 0)
                                $tprog = $ross;
                            if(strcasecmp("In Charge", $mike) == 0)
                                $icharge = $ross;
                            if(strcasecmp("In Charge Email", $mike) == 0)
                                $iemail = $ross;
                            if(strcasecmp("In Charge Phone", $mike) == 0)
                                $iphone = $ross;
                        }
                        $summary[] = [
                            'County' => $tcounty,
                            'Sub County' => $tsub_county,
                            'Facility' => $facility,
                            'MFL Code' => $mfl,
                            'Tester Enrollment ID' => $tuid,
                            'Tester First Name' => $tfirst_name,
                            'Tester Surname' => $tlast_name,
                            'Tester Other Name' => $tmiddle_name,
                            'Gender' => $tgender,
                            'Tester Mobile Number' => $tphone,
                            'Tester Email' => $temail,
                            'Tester Address' => $taddress,
                            'Designation' => $tdes,
                            'Program' => $tprog,
                            'In Charge' => $icharge,
                            'In Charge Email' => $iemail,
                            'In Charge Phone' => $iphone,
                        ];
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
                    $fIds = $county->facilities()->pluck('id');
                    $ids = DB::table('role_user')->where('role_id', $roleId)->whereIn('tier', $fIds)->pluck('user_id');
                    if($request->status)
                        $ids = User::whereIn('id', $ids)->whereBetween('date_registered', [$round->start_date, $round->end_date])->pluck('id');
                    
                    $testers = Enrol::where('round_id', $round->id)->whereIn('user_id', $ids)->pluck('user_id')->toArray();
                    if(count($testers) > 0)
                    {
                        $testers = implode(",", $testers);
                        // dd($testers);
                        $sheetTitle = $county->name;
                        if (count($testers)>0) {
                    $data = DB::select(
                        "SELECT
                        u.first_name AS 'Tester First Name',
                        u.last_name AS 'Tester Surname',
                        u.middle_name AS 'Tester Other Name',
                        u.uid AS 'Tester Enrollment ID',
                        u.gender AS 'Gender',
                        u.phone AS 'Tester Mobile Number',
                        u.email AS 'Tester Email',
                        u.address AS 'Tester Address',
                        p.name AS 'Program',
                        ru.designation AS 'Designation',
                        f.name AS 'Facility',
                        f.code AS 'MFL Code',
                        f.in_charge AS 'In Charge',
                        f.in_charge_phone AS 'In Charge Phone',
                        f.in_charge_email AS 'In Charge Email'
                        FROM users u, facilities f, role_user ru, programs p
                        WHERE u.id = ru.user_id
                            AND ru.program_id = p.id
                            AND ru.tier = f.id
                            AND ru.program_id = p.id
                            AND u.id
                        IN (".$testers.") ORDER BY u.uid ASC;");

                            // dd($data);
                            //  create associative array
                            $summary = [];
                            foreach($data as $key => $value)
                            {
                                $tfirst_name = NULL;
                                $tmiddle_name = NULL;
                                $tlast_name = NULL;
                                $tuid = NULL;
                                $tphone = NULL;
                                $temail = NULL;
                                $tprog = NULL;
                                $tdes = NULL;
                                $facility = NULL;
                                $mfl = NULL;
                                $icharge = NULL;
                                $iphone = NULL;
                                $iemail = NULL;
                                $tcounty = NULL;
                                $tsub_county = NULL;
                                $tgender = NULL;
                                $taddress = NULL;
                                foreach($value as $mike => $ross)
                                {
                                    if(strcasecmp("County", $mike) == 0)
                                        $tcounty = $ross;
                                    if(strcasecmp("Sub County", $mike) == 0)
                                        $tsub_county = $ross;
                                    if(strcasecmp("Facility", $mike) == 0)
                                        $facility = $ross;
                                    if(strcasecmp("MFL Code", $mike) == 0)
                                        $mfl = $ross;
                                    if(strcasecmp("Tester Enrollment ID", $mike) == 0)
                                        $tuid = $ross;
                                    if(strcasecmp("Tester First Name", $mike) == 0)
                                        $tfirst_name = $ross;
                                    if(strcasecmp("Tester Surname", $mike) == 0)
                                        $tlast_name = $ross;
                                    if(strcasecmp("Tester Other Name", $mike) == 0)
                                        $tmiddle_name = $ross;
                                    if(strcasecmp("Gender", $mike) == 0)
                                        $tgender = $ross;
                                    if(strcasecmp("Tester Mobile Number", $mike) == 0)
                                        $tphone = $ross;
                                    if(strcasecmp("Tester Email", $mike) == 0)
                                        $temail = $ross;
                                    if(strcasecmp("Tester Address", $mike) == 0)
                                        $taddress = $ross;
                                    if(strcasecmp("Designation", $mike) == 0)
                                        $tdes = $ross;
                                    if(strcasecmp("Program", $mike) == 0)
                                        $tprog = $ross;
                                    if(strcasecmp("In Charge", $mike) == 0)
                                        $icharge = $ross;
                                    if(strcasecmp("In Charge Email", $mike) == 0)
                                        $iemail = $ross;
                                    if(strcasecmp("In Charge Phone", $mike) == 0)
                                        $iphone = $ross;
                                }
                                $summary[] = [
                                    'County' => Facility::where('code', $mfl)->orderBy('name', 'asc')->first()->subCounty->name,
                                    'Sub County' => Facility::where('code', $mfl)->orderBy('name', 'asc')->first()->subCounty->county->name,
                                    'Facility' => $facility,
                                    'MFL Code' => $mfl,
                                    'Tester Enrollment ID' => $tuid,
                                    'Tester First Name' => $tfirst_name,
                                    'Tester Surname' => $tlast_name,
                                    'Tester Other Name' => $tmiddle_name,
                                    'Gender' => ($tgender == 1) ? 'Female' : 'Male',
                                    'Tester Mobile Number' => $tphone,
                                    'Tester Email' => $temail,
                                    'Tester Address' => $taddress,
                                    'Designation' => Designation::find($tdes)->name,
                                    'Program' => $tprog,
                                    'In Charge' => $icharge,
                                    'In Charge Email' => $iemail,
                                    'In Charge Phone' => $iphone,
                                ];
                            }
                        }
                        $excel->sheet($sheetTitle, function($sheet) use ($summary) {
                            $sheet->fromArray($summary);
                        });
                    }
                }
            }
        })->download('xlsx');
    }
    
    /**
     * Batch registration and enrollment
     *
     */
    public function batchRegisterAndEnrol(Request $request)
    {
        $rId = $request->id;
        $id = Round::find($rId)->name;
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
            $folder = '/batch/'.$id.'/'.$county.'/';
        }
        else
            $folder = '/batch/'.$id.'/nphls/';
        if(!is_dir(public_path().$folder))
            File::makeDirectory(public_path().$folder, 0777, true);
        file_put_contents(public_path().$folder.$fileName, $decoded);

        //  get today's date
        $today = Carbon::today();
        //  Handle the import
        //  Get the results
        //  Import a user provided file
        //  Convert file to csv
        if(Auth::user()->isCountyCoordinator())
            $loadedFile = Excel::load('public/batch/'.$id.'/'.$county.'/'.$fileName, function($reader) {$reader->ignoreEmpty();})->get();
        else
            $loadedFile = Excel::load('public/batch/'.$id.'/nphls/'.$fileName, function($reader) {$reader->ignoreEmpty();})->get();
        if(!empty($loadedFile) && $loadedFile->count())
        {
            $duplicates = array();
            foreach($loadedFile as $sheet){
                foreach ($sheet->toArray() as $harvey => $specter)
                {
                    if(!empty($specter))
                    {
                        $county = NULL;
                        $sub_county = NULL;
                        $facility = NULL;
                        $mfl = NULL;
                        $uid = NULL;
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
                            if(strcmp($mike, "tester_enrollment_id") === 0)
                                $uid = $ross;
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
                        //  process gender
                        if(strcmp($tgender, "Male") === 0)
                            $tgender = User::MALE;
                        else
                            $tgender = User::FEMALE;
                        //  process designation
                        $testerDes = $tdes;
                        if($testerDes)
                        {
                            $tdes = Designation::idByTitle($tdes);
                            if(!$tdes)
                            {
                                $des = new Designation;
                                $des->name = $testerDes;
                                $des->save();
                                $tdes = $des->id;
                            }
                        }

                        if(strcmp($county, "MURANGA") === 0)
                            $county = "Murang'a";
                        if(strcmp($county, "HOMABAY") === 0)
                            $county = "Homa Bay";
                        if(strcmp($county, "THARAKA-NITHI") === 0)
                            $county = "Tharaka Nithi";

                        //  process user details only if the name exists
                        if($uid)
                        {
                            $user = User::find(User::idByUid($uid));
                            if(!$user)
                            {
                                $user = new User;
                                $user->uid = $uid;
                            }
                        }
                        else
                        {
                            $userId = User::idByEmail($temail);
                            if($userId)
                            {
                                $user = User::find($userId);
                                $user->uid = $user->uid;
                            }
                            else
                            {
                                $user = new User;
                                $user->date_registered = $today;
                                $count = count(User::where('uid', User::MIN_UNIQUE_ID)->get());
                                if($count > 0)
                                    $user->uid = DB::table('users')->where('uid', '>=', User::MIN_UNIQUE_ID)->max('uid')+1;
                                else
                                    $user->uid = User::MIN_UNIQUE_ID;
                            }
                        }
                        //  process user details
                        if($tfname && $tsname && $tphone && $temail && $tprog && $tdes)
                        {
                            if(count(User::where('email', $temail)->orWhere('phone', $tphone)->get()) > 0){
                                $duplicateParticapant = array($tfname, $tsname, $tphone, $temail);
                                $duplicates[] = $duplicateParticapant;
                                continue;
                            }
                            $user->first_name = $tfname;
                            $user->middle_name = $toname;
                            $user->last_name = $tsname;
                            $user->gender = $tgender;
                            $user->email = $temail;
                            $user->phone = $tphone;
                            $user->address = $taddress;
                            $user->username = uniqid();
                            $user->phone_verified = 1;
                            $user->email_verified = 1;
                            $user->save();
                            $user->username = $user->uid;
                            $user->password = Hash::make(User::DEFAULT_PASSWORD);
                            $user->save();
                            $userId = $user->id;

                            //  Prepare to save new facility details
                            $facilityId = Facility::idByCode($mfl);
                            if(!$facilityId)
                            {
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
                            }else{
                                $fc = Facility::find($facilityId);
                                $fc->code = $mfl;
                                $fc->in_charge = $incharge;
                                $fc->in_charge_phone = $iphone;
                                $fc->in_charge_email = $iemail;
                                $fc->save();
                            }
                            
                            //  Prepare to save role-user details
                            $roleId = Role::idByName('Participant');
                            $user->detachAllRoles();
                            DB::table('role_user')->insert(['user_id' => $userId, 'role_id' => $roleId, 'tier' => $facilityId, 'program_id' => Program::idByTitle($tprog), "designation" => $tdes]);
                            //  Enrol the participant to the pt round
                            $userId = $user->id;
                            $roundId = $rId;
                            $enrol = Enrol::where('round_id', $roundId)->where('user_id', $userId)->get();
                            if(count($enrol) == 0)
                            {
                                $enrol = new Enrol;
                                $enrol->round_id = $roundId;
                                $enrol->user_id = $userId;
                                $enrol->save();
                            }
                            //  send email and sms for registration
                            
                            if($user->date_registered)
                            {
                                //  send email and sms
                                $token = app('auth.password.broker')->createToken($user);
                                $user->token = $token;
                                $user->notify(new WelcomeNote($user));
                                $message    = "Dear ".$user->name.", NPHL has approved your request to participate in PT. Your tester ID is ".$user->uid.". Use the link sent to your email to get started.";
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
                            //  Enrollment notifications
                            $round = Round::find($roundId)->name;
                            $message = Notification::where('template', Notification::ENROLMENT)->first()->message;
                            $message = ApiController::replace_between($message, '[', ']', $round);
                            $message = str_replace(' [', ' ', $message);
                            $message = str_replace('] ', ' ', $message);
                            $message = "Dear ".$user->name.", you have been enrolled to PT round ".$round.". If not participating, contact your county lab coordinator.";
                            try 
                            {
                                $smsHandler = new SmsHandler();
                                $smsHandler->sendMessage($user->phone, $message);
                            }
                            catch ( AfricasTalkingGatewayException $e )
                            {
                                echo "Encountered an error while sending: ".$e->getMessage();
                            }
                            $user->round = $round;                        
                            $user->notify(new EnrollmentNote($user));
                            //  Bulk-sms settings
                            
                        }
                    }
                }
            }
            return response()->json(array('errors' => $duplicates));
        }
    }
}
$excel = App::make('excel');

<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Pt;
use App\Result;
use App\Field;
use App\Option;
use App\User;
use App\Notification;
use App\Enrol;
use App\Round;
use App\County;
use App\SubCounty;
use App\Facility;
use App\Program;

use App\Libraries\AfricasTalkingGateway as Bulk;

use Auth;
use Jenssegers\Date\Date as Carbon;
use DB;

class ResultController extends Controller
{

    public function manageResult()
    {
        return view('result.index');
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $error = ['error' => 'No results found, please try with different keywords.'];
        $results = Pt::latest()->withTrashed()->paginate(5);
        if(Auth::user()->isCountyCoordinator())
        {
            $results = County::find(Auth::user()->ru()->tier)->results()->latest()->withTrashed()->paginate(5);
        }
        else if(Auth::user()->isSubCountyCoordinator())
        {
           $results = SubCounty::find(Auth::user()->ru()->tier)->results()->latest()->withTrashed()->paginate(5);
        }
        else if(Auth::user()->isFacilityInCharge())
        {
           $results = Facility::find(Auth::user()->ru()->tier)->results()->latest()->withTrashed()->paginate(5);
        }
        else if(Auth::user()->isParticipant())
        {
            $results = Auth::user()->results()->latest()->withTrashed()->paginate(5);
        }
        if($request->has('q')) 
        {
            $search = $request->get('q');
            $results = Pt::where('pt_id', 'LIKE', "%{$search}%")->latest()->withTrashed()->paginate(5);
        }
        foreach($results as $result)
        {
            $result->rnd = $result->enrolment->round->name;
            $result->tester = $result->enrolment->user->name;
        }
        $response = [
            'pagination' => [
                'total' => $results->total(),
                'per_page' => $results->perPage(),
                'current_page' => $results->currentPage(),
                'last_page' => $results->lastPage(),
                'from' => $results->firstItem(),
                'to' => $results->lastItem()
            ],
            'data' => $results
        ];
        return $results->count() > 0 ? response()->json($response) : $error;
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {           
                //return response()->json('Saved.');
        //Check if round has been         
        if ($request->get('round_id') =="") {
            return response()->json(['1']);            
        } else
        {
             //	Save pt first then proceed to save form fields
            $round_id = $request->get('round_id');
            $enrolment = Enrol::where('user_id', Auth::user()->id)->where('round_id', $round_id)->first();
            
            //Validation: Check if the enrolment results have been submitted
            if ($enrolment->status ==1) {
                return response()->json(['2']);            
            }else
            {        
                //	Proceed to form-fields
                foreach ($request->all() as $key => $value)
                {
                    if((stripos($key, 'token') !==FALSE) || (stripos($key, 'method') !==FALSE))
                        continue;
                    else if(stripos($key, 'field') !==FALSE)
                    {
                        $fieldId = $this->strip($key);
                        if(is_array($value))
                          $value = implode(', ', $value);
                        $result = new Result;
                        $result->pt_id = $pt->id;
                        $result->field_id = $fieldId;
                  		$result->response = $value;
                        $result->save();
                    }
                    else if(stripos($key, 'comment') !==FALSE)
                    {
                        if($value)
                        {
                            $result = Result::where('field_id', $key)->first();
                            $result->comment = $value;
                            $result->save();
                        }
                    }
                }    
                    $pt = new Pt;
                    $pt->enrolment_id = $enrolment->id;
                    $pt->panel_status = Pt::NOT_CHECKED;
                    $pt->save();

                    //update enrollment status to 1
                    $enrolment->status = Enrol::DONE;        
                    $enrolment->save();
                    //  Send SMS
                    $round = Round::find($pt->enrolment->round->id)->description;
                    $message = Notification::where('template', Notification::RESULTS_RECEIVED)->first()->message;
                    $message = $this->replace_between($message, '[', ']', $round);
                    $message = str_replace(' [', ' ', $message);
                    $message = str_replace(']', ' ', $message);

                    $created = Carbon::today()->toDateTimeString();
                    $updated = Carbon::today()->toDateTimeString();
                    //  Time
                    $now = Carbon::now('Africa/Nairobi');
                    $bulk = DB::table('bulk')->insert(['notification_id' => Notification::RESULTS_RECEIVED, 'round_id' => $pt->enrolment->round->id, 'text' => $message, 'user_id' => $pt->enrolment->user->id, 'date_sent' => $now, 'created_at' => $created, 'updated_at' => $updated]);
                 // dd($bulk);
                    $recipients = NULL;
                    $recipients = User::find($pt->enrolment->user->id)->value('phone');
                    //  Bulk-sms settings
                    $api = DB::table('bulk_sms_settings')->first();
                    $username   = $api->code;
                    $apikey     = $api->api_key;
                    if($recipients)
                    {
                        // Specified sender-id
                        // $from = $api->code;
                        $from ='Nat-HIVPT';
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
                return response()->json('Saved.');

             }
         }
    }

    /**
     * Fetch pt with related components for editing
     *
     * @param ID of the selected pt -  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $pt = Pt::find($id);
        $round = $pt->enrolment->round;
        $results = $pt->results;
        $response = [
            'pt' => $pt,
            'round' => $round,
            'results' => $results
        ];
        // dd($response);
        return response()->json($response);
    }
    /*
    *   verify the result after reviewing
    */
    public function verify(Request $request)
    {
        $id = $request->pt_id;
        $user_id = Auth::user()->id;

        $result = Pt::find($id);
        $result->verified_by = $user_id;
        $result->panel_status = Pt::VERIFIED;
        if($request->comment)
            $result->comment = $request->comment;
        $result->save();
        // Send SMS
        $round = Round::find($result->enrolment->round->id)->description;
        $message = Notification::where('template', Notification::FEEDBACK_RELEASE)->first()->message;
        $message = $this->replace_between($message, '[', ']', $round);
        $message = str_replace(' [', ' ', $message);
        $message = str_replace(']', ' ', $message);
        
        $created = Carbon::today()->toDateTimeString();
        $updated = Carbon::today()->toDateTimeString();
        //  Time
        $now = Carbon::now('Africa/Nairobi');
        $bulk = DB::table('bulk')->insert(['notification_id' => Notification::FEEDBACK_RELEASE, 'round_id' => $result->enrolment->round->id, 'text' => $message, 'user_id' => $result->enrolment->user->id, 'date_sent' => $now, 'created_at' => $created, 'updated_at' => $updated]);
     
        $recipients = NULL;
        $recipients = User::find($result->enrolment->user->id)->value('phone');
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
        return response()->json($result);
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
        /*$this->validate($request, [
            'round_id' => 'required',
            'date_prepared' => 'required',
            'date_shipped' => 'required',
            'shipping_method' => 'required',
            'shipper_id' => 'required',
            'facility_id' => 'required',
            'panels_shipped' => 'required',
        ]);*/
        $pt = Pt::find($id);
    
        //  Proceed to form-fields
        foreach ($request->all() as $key => $value)
        {
            if((stripos($key, 'token') !==FALSE) || (stripos($key, 'method') !==FALSE))
                continue;
            else if(stripos($key, 'field') !==FALSE)
            {
                $fieldId = $this->strip($key);
                if(is_array($value))
                  $value = implode(', ', $value);
                $result = new Result;
                $result->pt_id = $pt->id;
                $result->field_id = $fieldId;
                $result->response = $value;
                $result->save();
            }
            else if(stripos($key, 'comment') !==FALSE)
            {
                if($value)
                {
                    $result = Result::where('field_id', $key)->first();
                    $result->comment = $value;
                    $result->save();
                }
            }
        }    

        return response()->json(["Done"]);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        Shipment::find($id)->delete();
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
        $shipment = Shipment::withTrashed()->find($id)->restore();
        return response()->json(['done']);
    }

    /**
     * Receive a shipment.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function receive(Request $request)
    {
        $this->validate($request, [
            'date_received' => 'required',
            'panels_received' => 'required',
            'condition' => 'required',
            'receiver' => 'required'
        ]);

        $create = Receipt::create($request->all());

        return response()->json($create);
    }
    /**
  	 * Remove the specified begining of text to get Id alone.
  	 *
  	 * @param  int  $id
  	 * @return Response
  	 */
  	public function strip($field)
  	{
    		if(($pos = strpos($field, '_')) !== FALSE)
    		return substr($field, $pos+1);
  	}
    /**
     * Replace strings between two characters.
     *
     */
    function replace_between($str, $needle_start, $needle_end, $replacement) 
    {
        $pos = strpos($str, $needle_start);
        $start = $pos === false ? 0 : $pos + strlen($needle_start);

        $pos = strpos($str, $needle_end, $start);
        $end = $pos === false ? strlen($str) : $pos;

        return substr_replace($str, $replacement, $start, $end - $start);
    }
    /**
     * Fetch feedback for the given id
     *
     * @param ID of the selected pt -  $id
     */
    public function feedback($id)
    {
        $pt = Pt::find($id);
        $usr = User::find($pt->enrolment->user_id);
        $pt->uid = (string)$usr->uid;
        $pt->tester = $usr->name;
        $pt->program = Program::find(1)->name;
        $facility = Facility::find(1);
        $pt->county = strtoupper($facility->subCounty->county->name);
        $pt->sub_county = $facility->subCounty->name;
        $pt->facility = $facility->name;
        $pt->round = $pt->enrolment->round->name;
        $pt->verdict = $pt->outcome($pt->feedback);
        $pt->remark = '';
        if($pt->feedback == Pt::UNSATISFACTORY)
            $pt->remark = 'Reason: '.$pt->unsatisfactory();
        $pt->date_authorized = Carbon::parse($pt->updated_at)->toFormattedDateString();
        $response = [
            'data' => $pt
        ];

        return response()->json($response);
    }
}
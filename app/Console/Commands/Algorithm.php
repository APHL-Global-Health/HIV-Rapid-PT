<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

use DB;
use Hash;

use App\Dump;
use App\Round;
use App\User;
use App\Pt;
use App\Result;
use App\Facility;
use App\Program;
use App\Registration;
use App\Role;
use App\Nonperformance;
use App\Field;
use App\Option;
use App\Enrol;
use App\SubCounty;
use App\County;

use App\Http\Controllers\ResultController;

use Jenssegers\Date\Date as Carbon;

class Algorithm extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'algorithm';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        /**
        * 1. Get all results in the pt-dump and add them to pt table.
        *
        */
        //  Fetch all pending records
        $counter = Dump::count();
        if($counter > 0)
        {
            $dumps = Dump::all();
            $this->moveDump($dumps);
        }
        /**
        * 2. Get all unchecked results and execute the algorithm.
        *
        */
        $counter = Pt::where('panel_status', Pt::CHECKED)->count();
        if($counter > 0);
        {
            $pts = Pt::where('panel_status', Pt::CHECKED)->get();
            $this->runAlgorithm($pts);
        }
        $this->info('Scheduled:Cron Command Run successfully!');
    }
    /**
    * Function to move data from pt-dump to pt table
    *
    * @return \Illuminate\Http\Response
    */
    public function moveDump($dumps)
    {
        foreach($dumps as $dump)
        {        
            //  Check if new tester
            $userId = NULL;
            if(!empty($dump->Participant_Full_Names))
            {
                $usr = User::idByName($dump->Participant_Full_Names);
                if($usr)
                {
                    $userId = $usr;
                }
                else
                {
                    $user = new User;
                    $user->name = $dump->Participant_Full_Names;
                    $user->email = $dump->NT_Email;
                    $user->password = Hash::make(User::DEFAULT_PASSWORD);
                    $user->address = NULL;
                    $user->gender = User::gender($dump->NTester_gender);
                    $user->phone = "+254$dump->NTMobile_Number";
                    $user->username = substr(str_replace(' ','',strtolower($dump->Participant_Full_Names)), 0, 5).rand(1,3);
                    $user->save();
                    //  Update username
                    $user->username = $user->id;
                    $user->uid = $user->id;
                    $user->save();
                    $userId = $user->id;
                }
                //  Creare role-user
                $role = Role::idByName('Participant');
                if($dump->MFL_Code)
                {
                    $facility = Facility::idByCode($dump->MFL_Code);
                }
                else
                {
                    if($dump->Facility_Name)
                        $facility = Facility::idByName($dump->Facility_Name);
                    if(!$facility)
                    {
                        $facility = new Facility;
                        $facility->name = strtoupper($dump->Facility_Name);
                        $sub = SubCounty::idByName($dump->SUBCOUNTY);
                        if(!$sub)
                            $facility->sub_county_id = SubCounty::idByName($dump->SUBCOUNTY);
                        else
                        {
                            $sb = new SubCounty;
                            $sb->name = $dump->SUBCOUNTY;
                            $sb->county_id = County::idByName($dump->COUNTY);
                            $sb->save();
                            $facility->sub_county_id = $sb->id;                        
                        }
                        $facility->save();
                    }
                }
                if($dump->NT_Program == "LVCT")
                    $program_id = Program::idByTitle("VCT");
                else
                    $program_id = Program::idByTitle($dump->NT_Program);
                //  Check if role-user exists
                $count = DB::table('role_user')->where('user_id', $userId)->where('role_id', $role)->count();
                if($count == 0)
                {
                    DB::table('role_user')->insert(["user_id" => $userId, "role_id" => $role, "tier" => $facility, "program_id" => $program_id]);
                }
                //  Reasons for non-performance to registration
                //  Check if user already registered
                $regCount = Registration::where('user_id', $userId)->where('uid', $dump->ID_No)->count();
                if($regCount == 0)
                {
                    $reg = new Registration;
                    $reg->user_id = $userId;
                    $reg->uid = $dump->ID_No;
                    if(!empty($dump->Transferred_County) || !empty($dump->Transferred_Facility))
                    {
                        $reg->nonperformance_id = Nonperformance::idByTitle('Transferred');
                        $reg->comment = $dump->Transferred_Facility;
                    }
                    else if(!empty($dump->Tester_Off_Duty))
                        $reg->nonperformance_id = Nonperformance::idByTitle('Off Duty');
                    else
                        $reg->nonperformance_id = Nonperformance::idByTitle('Other');
                    $reg->save();
                }
            }
            else
            {
                //  check if the user with the given uID exists
                if($dump->ID_No)
                    $userId = User::idByUID($dump->ID_No);
                else
                    $userId = User::idByName($dump->Tester_Name);
            }
            //  Enrol user to the available round
            $round = Round::idByTitle($dump->Round);
            //  Check if record already exists
            $enrolment = null;
            if(Enrol::where('user_id', $userId)->where('round_id', $round)->count() != 0)
            {
                $enrolment = Enrol::where('user_id', $userId)->where('round_id', $round)->first();
            }
            else // Add new enrolment record
            {
                $enrolment = new Enrol;
                $enrolment->user_id = $userId;
                $enrolment->round_id = $round;
                $enrolment->save();
            }
            //  Check if record exists
            $pt = null;
            if(count($enrolment->pt) != 0)
            {
                $pt = $enrolment->pt->first();
            }
            else
            {
                //  Save to pt table
                $pt = new Pt;
                $pt->enrolment_id = $enrolment->id;
                $pt->panel_status = Pt::NOT_CHECKED;
                $pt->comment = $dump->Comments;
                $pt->save();
            }

            //  Save results - one by one for the whole form
            //  Date received
            $dateReceived = $this->saveToPt($pt->id, 'Date PT Panel Received', $dump->Panel_recv_Date);
            //  Date constituted
            $dateConstituted = $this->saveToPt($pt->id, 'Date PT Panel Constituted', $dump->Panel_Const_Date);
            //  Date tested
            $dateTested = $this->saveToPt($pt->id, 'Date PT Panel Tested', $dump->Panel_Tested_Date);
            //  Test 1 kit name
            $test1kitName = $this->saveToPt($pt->id, 'Test 1 Kit Name', $dump->Test1_Name);
            //  Test 1 kit lot no
            $test1kitLotNo = $this->saveToPt($pt->id, 'Test 1 Lot No.', $dump->Kit1_Lot_No);
            //  Test 1 kit expiry date
            $test1kitExpiryDate = $this->saveToPt($pt->id, 'Test 1 Expiry Date', $dump->Kit1_Exp_Date);
            //  Test 2 kit name
            $test2kitName = $this->saveToPt($pt->id, 'Test 2 Kit Name', $dump->Test2_Name);
            //  Test 2 kit lot no
            $test2kitLotNo = $this->saveToPt($pt->id, 'Test 2 Lot No.', $dump->Kit2_Lot_No);
            //  Test 2 kit expiry date
            $test2kitExpiryDate = $this->saveToPt($pt->id, 'Test 2 Expiry Date', $dump->Kit2_Exp_Date);
            //  Test 3 kit name
            $test3kitName = $this->saveToPt($pt->id, 'Test 3 Kit Name', $dump->Test3_Name);
            //  Test 3 kit lot no
            $test3kitLotNo = $this->saveToPt($pt->id, 'Test 3 Lot No.', $dump->Kit3_Lot_No);
            //  Test 3 kit expiry date
            $test3kitExpiryDate = $this->saveToPt($pt->id, 'Test 3 Expiry Date', $dump->Kit3_Exp_Date);
            //  Panel 1 test 1 results
            $ptPanel1Test1Result = $this->saveToPt($pt->id, 'PT Panel 1 Test 1 Results', $dump->PT1TEST1Results);
            //  Panel 1 test 2 results
            $ptPanel1Test2Result = $this->saveToPt($pt->id, 'PT Panel 1 Test 2 Results', $dump->PT1TEST2Results);
            //  Panel 1 test 3 results
            $ptPanel1Test3Result = $this->saveToPt($pt->id, 'PT Panel 1 Test 3 Results', $dump->PT1TEST3Results);
            //  Panel 1 final results
            $ptPanel1FinalResult = $this->saveToPt($pt->id, 'PT Panel 1 Final Results', $dump->PT1FinalResults);
            //  Panel 2 test 1 results
            $ptPanel2Test1Result = $this->saveToPt($pt->id, 'PT Panel 2 Test 1 Results', $dump->PT2TEST1Results);
            //  Panel 2 test 2 results
            $ptPanel2Test2Result = $this->saveToPt($pt->id, 'PT Panel 2 Test 2 Results', $dump->PT2TEST2Results);
            //  Panel 2 test 3 results
            $ptPanel2Test3Result = $this->saveToPt($pt->id, 'PT Panel 2 Test 3 Results', $dump->PT2TEST3Results);
            //  Panel 2 final results
            $ptPanel2FinalResult = $this->saveToPt($pt->id, 'PT Panel 2 Final Results', $dump->PT2FinalResults);
            //  Panel 3 test 1 results
            $ptPanel3Test1Result = $this->saveToPt($pt->id, 'PT Panel 3 Test 1 Results', $dump->PT3TEST1Results);
            //  Panel 3 test 2 results
            $ptPanel3Test2Result = $this->saveToPt($pt->id, 'PT Panel 3 Test 2 Results', $dump->PT3TEST2Results);
            //  Panel 3 test 3 results
            $ptPanel3Test3Result = $this->saveToPt($pt->id, 'PT Panel 3 Test 3 Results', $dump->PT3TEST3Results);
            //  Panel 3 final results
            $ptPanel3FinalResult = $this->saveToPt($pt->id, 'PT Panel 3 Final Results', $dump->PT3FinalResults);
            //  Panel 4 test 1 results
            $ptPanel4Test1Result = $this->saveToPt($pt->id, 'PT Panel 4 Test 1 Results', $dump->PT4TEST1Results);
            //  Panel 4 test 2 results
            $ptPanel4Test2Result = $this->saveToPt($pt->id, 'PT Panel 4 Test 2 Results', $dump->PT4TEST2Results);
            //  Panel 4 test 3 results
            $ptPanel4Test3Result = $this->saveToPt($pt->id, 'PT Panel 4 Test 3 Results', $dump->PT4TEST3Results);
            //  Panel 4 final results
            $ptPanel4FinalResult = $this->saveToPt($pt->id, 'PT Panel 4 Final Results', $dump->PT4FinalResults);
            //  Panel 5 test 1 results
            $ptPanel5Test1Result = $this->saveToPt($pt->id, 'PT Panel 5 Test 1 Results', $dump->PT5TEST1Results);
            //  Panel 5 test 2 results
            $ptPanel5Test2Result = $this->saveToPt($pt->id, 'PT Panel 5 Test 2 Results', $dump->PT5TEST2Results);
            //  Panel 5 test 3 results
            $ptPanel5Test3Result = $this->saveToPt($pt->id, 'PT Panel 5 Test 3 Results', $dump->PT5TEST3Results);
            //  Panel 5 final results
            $ptPanel5FinalResult = $this->saveToPt($pt->id, 'PT Panel 5 Final Results', $dump->PT5FinalResults);
            //  Panel 6 test 1 results
            $ptPanel6Test1Result = $this->saveToPt($pt->id, 'PT Panel 6 Test 1 Results', $dump->PT6TEST1Results);
            //  Panel 6 test 2 results
            $ptPanel6Test2Result = $this->saveToPt($pt->id, 'PT Panel 6 Test 2 Results', $dump->PT6TEST2Results);
            //  Panel 6 test 3 results
            $ptPanel6Test3Result = $this->saveToPt($pt->id, 'PT Panel 6 Test 3 Results', $dump->PT6TEST3Results);
            //  Panel 6 final results
            $ptPanel6FinalResult = $this->saveToPt($pt->id, 'PT Panel 6 Final Results', $dump->PT6FinalResults);
            //  Soft-delete the saved dump
            $dump->delete();
        }
    }
    /**
    * Function to save data to the pt table
    *
    * $field = name of field, $dmp = corresponding value in pt_dump
    */
    public function saveToPt($ptId, $field, $dmp = NULL, $comment = NULL)
    {
        if($field_id = Field::idByUID($field))
        {
            if($field_id == Field::idByUID("Test 1 Kit Name") && $dmp == "Determine")
            {
                $comment = $dmp;
                $dmp = "Other";
            }
            if (strpos($dmp, '/') !== false)
            {
                if(empty($this->strip($this->strip($dmp))))
                    $dmp = NULL;
                else{
                    $dmp = Carbon::createFromFormat('d/m/Y', $dmp)->toDateString();
                }
            }
            if(!empty($dmp))
            {
                if(Result::where('pt_id', $ptId)->where('field_id', $field_id)->count() == 0)
                    $result = new Result;
                else
                    $result = Result::where('pt_id', $ptId)->where('field_id', $field_id)->first();
                $fld = Field::find($field_id);
                $result->pt_id = $ptId;
                $result->field_id = $field_id;
                if (preg_match('/_/', $dmp))
                    $dmp = str_replace('_', ' ', $dmp);
                if($fld->tag == 5)
                    $result->response = Option::idByTitle($dmp);
                else
                    $result->response = $dmp;
                if(!empty($comment))
                    $result->comment = $comment;
                $result->save();
            }
        }
        else
        {
            print($field);
        }
        return response()->json('Saved.');
    }
    /**
     * Remove the specified begining of text to get Id alone.
     *
     * @param  int  $id
     * @return Response
     */
    public function strip($field)
    {
        if(($pos = strpos($field, '/')) !== FALSE)
            return substr($field, $pos+1);
    }
    /**
     * Begin algorithm to mark test results
     */
    /**
     * Function to check dates - received, constituted, tested
     *
     * @param  $date_pt_panel_received, $date_consituted, $date_pt_panel_tested
     * @return Deviation from procedure.
     */
     public function check_dates($date_pt_panel_received, $date_pt_panel_constituted, $date_pt_panel_tested)
     {
         // Check Dates
         $dev_from_procedure = 0;
         $dt_constituted = Carbon::parse($date_pt_panel_constituted);
         $dt_tested = Carbon::parse($date_pt_panel_tested);
         if(strtotime($date_pt_panel_tested) == strtotime($date_pt_panel_constituted) || $dt_constituted->diffInDays($dt_tested) > 1)
            $dev_from_procedure = 1;
         return $dev_from_procedure;
     }
    /**
     * Function to check other info - received, constituted, tested
     *
     * @param  $date_pt_panel_received, $date_consituted, $date_pt_panel_tested
     * @return Deviation from procedure.
     */
     public function check_other_info($date_pt_panel_received, $date_pt_panel_constituted, $date_pt_panel_tested)
     {
         // Check Dates
         $incomplete_other_info = 0;
         if($date_pt_panel_tested === NULL || $date_pt_panel_constituted === NULL || $date_pt_panel_tested === NULL)
            $incomplete_other_info = 1;
         return $incomplete_other_info;
     }
    /**
     * Function to completeness of HIV test kits info
     *
     * @param  $test_1_kit_name, $test_2_kit_name, $test_1_kit_lot_no, $test_2_kit_lot_no, $test_1_expiry_date, $test_2_expiry_date
     * @return Incomplete kit data.
     */
     public function check_kit_info($test_1_kit_name, $test_2_kit_name, $test_1_kit_lot_no, $test_2_kit_lot_no, $test_1_expiry_date, $test_2_expiry_date)
     {
         // Check kit info
         $incomplete_kit_info = 0;
         if(empty($test_1_kit_name) || empty($test_2_kit_name) || empty($test_1_kit_lot_no) || empty($test_2_kit_lot_no) || empty($test_1_expiry_date) || empty($test_2_expiry_date))
             $incomplete_kit_info = 1;
         return $incomplete_kit_info;
     }
    /**
     * Function to check kit expiry against date tested
     *
     * @param  $date_pt_panel_tested, $date_consituted, $date_pt_panel_tested
     * @return Deviation from procedure.
     */
     public function check_expiry($date_pt_panel_tested, $test_1_expiry_date, $test_2_expiry_date, $test_3_expiry_date)
     {
         $use_of_expired_kits = 0;
         $dt_tested = Carbon::parse($date_pt_panel_tested);
         $dt_1_expiry = Carbon::parse($test_1_expiry_date);
         $dt_2_expiry = Carbon::parse($test_2_expiry_date);
         $dt_3_expiry = Carbon::parse($test_3_expiry_date);
         if($dt_tested->gt($dt_1_expiry) || $dt_tested->gt($dt_2_expiry) || $dt_tested->gt($dt_3_expiry))
            $use_of_expired_kits = 1;
         return $use_of_expired_kits;
     }
    /**
     * Function to check completeness of results
     *
     * @param  Test results, Final results
     * @return Incomplete Results.
     */
     public function check_complete_results($pt_panel_1_test_1_results, $pt_panel_1_final_results, $pt_panel_2_test_1_results, $pt_panel_2_final_results, $pt_panel_3_test_1_results, $pt_panel_3_final_results, $pt_panel_4_test_1_results, $pt_panel_4_final_results, $pt_panel_5_test_1_results, $pt_panel_5_final_results, $pt_panel_6_test_1_results, $pt_panel_6_final_results)
     {
         $incomplete_results = 0;
         $reactive = Option::idByTitle('Reactive');
         $non_reactive = Option::idByTitle('Non Reactive');
         $not_done = Option::idByTitle('Not Done');
         if(
             ((($pt_panel_1_test_1_results == $reactive) || ($pt_panel_1_final_results == $non_reactive)) && ($pt_panel_1_final_results === NULL || ($pt_panel_1_final_results == $not_done))) ||
             ((($pt_panel_2_test_1_results == $reactive) || ($pt_panel_2_final_results == $non_reactive)) && ($pt_panel_2_final_results === NULL || ($pt_panel_2_final_results == $not_done))) ||
             ((($pt_panel_3_test_1_results == $reactive) || ($pt_panel_3_final_results == $non_reactive)) && ($pt_panel_3_final_results === NULL || ($pt_panel_3_final_results == $not_done))) ||
             ((($pt_panel_4_test_1_results == $reactive) || ($pt_panel_4_final_results == $non_reactive)) && ($pt_panel_4_final_results === NULL || ($pt_panel_4_final_results == $not_done))) ||
             ((($pt_panel_5_test_1_results == $reactive) || ($pt_panel_5_final_results == $non_reactive)) && ($pt_panel_5_final_results === NULL || ($pt_panel_5_final_results == $not_done))) ||
             ((($pt_panel_6_test_1_results == $reactive) || ($pt_panel_6_final_results == $non_reactive)) && ($pt_panel_6_final_results === NULL || ($pt_panel_6_final_results == $not_done)))
         )
            $incomplete_results = 1;
         return $incomplete_results;
     }
    /**
     * Function to check correctness of results
     *
     * @param  $date_pt_panel_received, $date_consituted, $date_pt_panel_tested
     * @return Incorrect results.
     */
     public function check_correct_results($pt_panel_1_final_results, $pt_panel_2_final_results, $pt_panel_3_final_results, $pt_panel_4_final_results, $pt_panel_5_final_results, $pt_panel_6_final_results, $ex_1, $ex_2, $ex_3, $ex_4, $ex_5, $ex_6)
     {
         // Check correctness
         $incorrect_results = 1;
         $indeterminate = Option::idByTitle('Indeterminate');
         if(
             ($pt_panel_1_final_results == $ex_1 || $pt_panel_1_final_results == $indeterminate) &&
             ($pt_panel_2_final_results == $ex_2 || $pt_panel_2_final_results == $indeterminate) &&
             ($pt_panel_3_final_results == $ex_3 || $pt_panel_3_final_results == $indeterminate) &&
             ($pt_panel_4_final_results == $ex_4 || $pt_panel_4_final_results == $indeterminate) &&
             ($pt_panel_5_final_results == $ex_5 || $pt_panel_5_final_results == $indeterminate) &&
             ($pt_panel_6_final_results == $ex_6 || $pt_panel_6_final_results == $indeterminate)
         )
            $incorrect_results = 0;
         return $incorrect_results;
     }
    /**
     * Function to check if results satisfactory
     *
     * @param  $incorrect_results
     * @return Unsatisfactory results.
     */
     public function check_satisfaction($incorrect_results)
     {
         $unsatisfactory = 0;
         if($incorrect_results == 1)
            $unsatisfactory = 1;
         return $unsatisfactory;
     }
    /**
     * Function to check if results are valid
     *
     * @param  Test results
     * @return Invalid results.
     */
     public function check_validity($pt_panel_1_test_1_results, $pt_panel_1_test_2_results, $pt_panel_1_test_3_results, $pt_panel_1_final_results, $pt_panel_2_test_1_results, $pt_panel_2_test_2_results, $pt_panel_2_test_3_results, $pt_panel_2_final_results, $pt_panel_3_test_1_results, $pt_panel_3_test_2_results, $pt_panel_3_test_3_results, $pt_panel_3_final_results, $pt_panel_4_test_1_results, $pt_panel_4_test_2_results, $pt_panel_4_test_3_results, $pt_panel_4_final_results, $pt_panel_5_test_1_results, $pt_panel_5_test_2_results, $pt_panel_5_test_3_results, $pt_panel_5_final_results, $pt_panel_6_test_1_results, $pt_panel_6_test_2_results, $pt_panel_6_test_3_results, $pt_panel_6_final_results)
     {
         $invalid_results = 0;
         $invalid = Option::idByTitle('Invalid');
         if(
             ($pt_panel_1_test_1_results == $invalid || $pt_panel_1_test_2_results == $invalid || $pt_panel_1_test_3_results == $invalid || $pt_panel_1_final_results == $invalid) || 
             ($pt_panel_2_test_1_results == $invalid || $pt_panel_2_test_2_results == $invalid || $pt_panel_2_test_3_results == $invalid || $pt_panel_2_final_results == $invalid) || 
             ($pt_panel_3_test_1_results == $invalid || $pt_panel_3_test_2_results == $invalid || $pt_panel_3_test_3_results == $invalid || $pt_panel_3_final_results == $invalid) || 
             ($pt_panel_4_test_1_results == $invalid || $pt_panel_4_test_2_results == $invalid || $pt_panel_4_test_3_results == $invalid || $pt_panel_4_final_results == $invalid) || 
             ($pt_panel_5_test_1_results == $invalid || $pt_panel_5_test_2_results == $invalid || $pt_panel_5_test_3_results == $invalid || $pt_panel_5_final_results == $invalid) || 
             ($pt_panel_6_test_1_results == $invalid || $pt_panel_6_test_2_results == $invalid || $pt_panel_6_test_3_results == $invalid || $pt_panel_6_final_results == $invalid)
        )
            $invalid_results = 1;
         return $invalid_results;
     }
    /**
     * Function to check if algorithm followed
     *
     * @param  Test results
     * @return Wrong algorithm.
     */
     public function check_algorithm($pt_panel_1_test_1_results, $pt_panel_1_test_2_results, $pt_panel_1_test_3_results, $pt_panel_1_final_results, $pt_panel_2_test_1_results, $pt_panel_2_test_2_results, $pt_panel_2_test_3_results, $pt_panel_2_final_results, $pt_panel_3_test_1_results, $pt_panel_3_test_2_results, $pt_panel_3_test_3_results, $pt_panel_3_final_results, $pt_panel_4_test_1_results, $pt_panel_4_test_2_results, $pt_panel_4_test_3_results, $pt_panel_4_final_results, $pt_panel_5_test_1_results, $pt_panel_5_test_2_results, $pt_panel_5_test_3_results, $pt_panel_5_final_results, $pt_panel_6_test_1_results, $pt_panel_6_test_2_results, $pt_panel_6_test_3_results, $pt_panel_6_final_results, $kit_1, $kit_2)
     {
         $wrong_algorithm = 1;
         $reactive = Option::idByTitle('Reactive');
         $non_reactive = Option::idByTitle('Non Reactive');
         $not_done = Option::idByTitle('Not Done');
         $fr = Option::idByTitle('First Response');
         $det = Option::idByTitle('Determine');
         //$unigold = Option::idByTitle('Unigold');
         if(
             ($pt_panel_1_test_1_results == $non_reactive && $pt_panel_1_test_2_results == $not_done) || ($pt_panel_1_test_1_results == $reactive && ($pt_panel_1_test_2_results == $non_reactive || $pt_panel_1_test_2_results == $reactive)) || 
             ($pt_panel_2_test_1_results == $non_reactive && $pt_panel_2_test_2_results == $not_done) || ($pt_panel_2_test_1_results == $reactive && ($pt_panel_2_test_2_results == $non_reactive || $pt_panel_2_test_2_results == $reactive)) || 
             ($pt_panel_3_test_1_results == $non_reactive && $pt_panel_3_test_2_results == $not_done) || ($pt_panel_3_test_1_results == $reactive && ($pt_panel_3_test_2_results == $non_reactive || $pt_panel_3_test_2_results == $reactive)) || 
             ($pt_panel_4_test_1_results == $non_reactive && $pt_panel_4_test_2_results == $not_done) || ($pt_panel_4_test_1_results == $reactive && ($pt_panel_4_test_2_results == $non_reactive || $pt_panel_4_test_2_results == $reactive)) || 
             ($pt_panel_5_test_1_results == $non_reactive && $pt_panel_5_test_2_results == $not_done) || ($pt_panel_5_test_1_results == $reactive && ($pt_panel_5_test_2_results == $non_reactive || $pt_panel_5_test_2_results == $reactive)) || 
             ($pt_panel_6_test_1_results == $non_reactive && $pt_panel_6_test_2_results == $not_done) || ($pt_panel_6_test_1_results == $reactive && ($pt_panel_6_test_2_results == $non_reactive || $pt_panel_6_test_2_results == $reactive)) || (($kit_1 == $det ) && ($kit_2 == $fr ))    
            )
            $wrong_algorithm = 0;
         return $wrong_algorithm;
     }
    /**
     * Function to set overall result - satisfactory/unsatisfactory
     *
     * @param  $dev_from_procedure, $incomplete_other_info, $incomplete_kit_info, $use_of_expired_kits, $incomplete_results, $incorrect_results, $unsatisfactory, $invalid, $wrong_algorithm
     * @return Unsatisfactory results.
     */
     public function check_overall($dev_from_procedure, $incomplete_other_info, $incomplete_kit_info, $use_of_expired_kits, $incomplete_results, $incorrect_results, $unsatisfactory, $invalid, $wrong_algorithm)
     {
         $overall = 1;
         if($dev_from_procedure == 0 && $incomplete_other_info == 0 && $incomplete_kit_info == 0 && $use_of_expired_kits == 0 && $incomplete_results == 0 && $incorrect_results == 0 && $unsatisfactory == 0 && $invalid == 0 && $wrong_algorithm == 0)
            $overall = 0;
         return $overall;
     }
     /**
     * Begin background processing
     */
     public function runAlgorithm($pts)
     {
        foreach($pts as $pt)
        {
            //  Fetch expected results
            $round = $pt->enrolment->round_id;
            $user = $pt->enrolment->user;
            if($pt->enrolment->user->registration)
                $user = User::where('uid', $user->registration->uid)->first();
            $lot = $user->lot($round);

            $res_1 = $lot->panels()->where('panel', 1)->first();
            $res_2 = $lot->panels()->where('panel', 2)->first();
            $res_3 = $lot->panels()->where('panel', 3)->first();
            $res_4 = $lot->panels()->where('panel', 4)->first();
            $res_5 = $lot->panels()->where('panel', 5)->first();
            $res_6 = $lot->panels()->where('panel', 6)->first();

            $ex_1 = Option::idByTitle($res_1->result($res_1->result));
            $ex_2 = Option::idByTitle($res_2->result($res_2->result));
            $ex_3 = Option::idByTitle($res_3->result($res_3->result));
            $ex_4 = Option::idByTitle($res_4->result($res_4->result));
            $ex_5 = Option::idByTitle($res_5->result($res_5->result));
            $ex_6 = Option::idByTitle($res_6->result($res_6->result));
            //  End fetch
            $rs = $pt->results;
            $date_pt_panel_received = NULL;
            $date_pt_panel_constituted = NULL;
            $date_pt_panel_tested = NULL;
            $test_1_kit_name = NULL;
            $test_2_kit_name = NULL;
            $test_1_kit_lot_no = NULL;
            $test_2_kit_lot_no = NULL;
            $test_1_expiry_date = NULL;
            $test_2_expiry_date = NULL;
            $test_3_expiry_date = NULL;
            //  Kit names
            $kit_1 = NULL;
            $kit_2 = NULL;

            $pt_panel_1_test_1_results = NULL;
            $pt_panel_1_test_2_results = NULL;
            $pt_panel_1_test_3_results = NULL;
            $pt_panel_1_final_results = NULL;
            $pt_panel_2_test_1_results = NULL;
            $pt_panel_2_test_2_results = NULL;
            $pt_panel_2_test_3_results = NULL;
            $pt_panel_2_final_results = NULL;
            $pt_panel_3_test_1_results = NULL;
            $pt_panel_3_test_2_results = NULL;
            $pt_panel_3_test_3_results = NULL;
            $pt_panel_3_final_results = NULL;
            $pt_panel_4_test_1_results = NULL;
            $pt_panel_4_test_2_results = NULL;
            $pt_panel_4_test_3_results = NULL;
            $pt_panel_4_final_results = NULL;
            $pt_panel_5_test_1_results = NULL;
            $pt_panel_5_test_2_results = NULL;
            $pt_panel_5_test_3_results = NULL;
            $pt_panel_5_final_results = NULL;
            $pt_panel_6_test_1_results = NULL;
            $pt_panel_6_test_2_results = NULL;
            $pt_panel_6_test_3_results = NULL;
            $pt_panel_6_final_results = NULL;
            foreach($rs as $rss)
            {
                //  Get all variables first to be used after the loop
                if($rss->field_id == Field::idByUID('Date PT Panel Received'))
                    $date_pt_panel_received = $rss->response;
                if($rss->field_id == Field::idByUID('Date PT Panel Constituted'))
                    $date_pt_panel_constituted = $rss->response;
                if($rss->field_id == Field::idByUID('Date PT Panel Tested'))
                    $date_pt_panel_tested = $rss->response;
                if($rss->field_id == Field::idByUID('Test 1 Kit Name')){
                    $test_1_kit_name = $rss->response;
                    $kit_1 = $rss->response;
                    if($kit_1 == Option::idByTitle("Other"))
                        $kit_1 = $rss->comment;
                }
                if($rss->field_id == Field::idByUID('Test 2 Kit Name')){
                    $test_2_kit_name = $rss->response;
                    $kit_2 = $rss->response;
                }
                if($rss->field_id == Field::idByUID('Test 1 Lot No.'))
                    $test_1_kit_lot_no = $rss->response;
                if($rss->field_id == Field::idByUID('Test 2 Lot No.'))
                    $test_2_kit_lot_no = $rss->response;
                if($rss->field_id == Field::idByUID('Test 1 Expiry Date'))
                    $test_1_expiry_date = $rss->response;
                if($rss->field_id == Field::idByUID('Test 2 Expiry Date'))
                    $test_2_expiry_date = $rss->response;
                if($rss->field_id == Field::idByUID('Test 3 Expiry Date'))
                    $test_3_expiry_date = $rss->response;

                if($rss->field_id == Field::idByUID('PT Panel 1 Test 1 Results'))
                    $pt_panel_1_test_1_results = $rss->response;
                if($rss->field_id == Field::idByUID('PT Panel 1 Test 2 Results'))
                    $pt_panel_1_test_2_results = $rss->response;
                if($rss->field_id == Field::idByUID('PT Panel 1 Test 3 Results'))
                    $pt_panel_1_test_3_results = $rss->response;
                if($rss->field_id == Field::idByUID('PT Panel 1 Final Results'))
                    $pt_panel_1_final_results = $rss->response;
                if($rss->field_id == Field::idByUID('PT Panel 2 Test 1 Results'))
                    $pt_panel_2_test_1_results = $rss->response;
                if($rss->field_id == Field::idByUID('PT Panel 2 Test 2 Results'))
                    $pt_panel_2_test_2_results = $rss->response;
                if($rss->field_id == Field::idByUID('PT Panel 2 Test 3 Results'))
                    $pt_panel_2_test_3_results = $rss->response;
                if($rss->field_id == Field::idByUID('PT Panel 2 Final Results'))
                    $pt_panel_2_final_results = $rss->response;
                if($rss->field_id == Field::idByUID('PT Panel 3 Test 1 Results'))
                    $pt_panel_3_test_1_results = $rss->response;
                if($rss->field_id == Field::idByUID('PT Panel 3 Test 2 Results'))
                    $pt_panel_3_test_2_results = $rss->response;
                if($rss->field_id == Field::idByUID('PT Panel 3 Test 3 Results'))
                    $pt_panel_3_test_3_results = $rss->response;
                if($rss->field_id == Field::idByUID('PT Panel 3 Final Results'))
                    $pt_panel_3_final_results = $rss->response;
                if($rss->field_id == Field::idByUID('PT Panel 4 Test 1 Results'))
                    $pt_panel_4_test_1_results = $rss->response;
                if($rss->field_id == Field::idByUID('PT Panel 4 Test 2 Results'))
                    $pt_panel_4_test_2_results = $rss->response;
                if($rss->field_id == Field::idByUID('PT Panel 4 Test 3 Results'))
                    $pt_panel_4_test_3_results = $rss->response;
                if($rss->field_id == Field::idByUID('PT Panel 4 Final Results'))
                    $pt_panel_4_final_results = $rss->response;
                if($rss->field_id == Field::idByUID('PT Panel 5 Test 1 Results'))
                    $pt_panel_5_test_1_results = $rss->response;
                if($rss->field_id == Field::idByUID('PT Panel 5 Test 2 Results'))
                    $pt_panel_5_test_2_results = $rss->response;
                if($rss->field_id == Field::idByUID('PT Panel 5 Test 3 Results'))
                    $pt_panel_5_test_3_results = $rss->response;
                if($rss->field_id == Field::idByUID('PT Panel 5 Final Results'))
                    $pt_panel_5_final_results = $rss->response;
                if($rss->field_id == Field::idByUID('PT Panel 6 Test 1 Results'))
                    $pt_panel_6_test_1_results = $rss->response;
                if($rss->field_id == Field::idByUID('PT Panel 6 Test 2 Results'))
                    $pt_panel_6_test_2_results = $rss->response;
                if($rss->field_id == Field::idByUID('PT Panel 6 Test 3 Results'))
                    $pt_panel_6_test_3_results = $rss->response;
                if($rss->field_id == Field::idByUID('PT Panel 6 Final Results'))
                    $pt_panel_6_final_results = $rss->response;
            }
            //  Fetch expected results
            
            $dev_from_procedure = $this->check_dates($date_pt_panel_received, $date_pt_panel_constituted, $date_pt_panel_tested);
            $incomplete_other_info = $this->check_other_info($date_pt_panel_received, $date_pt_panel_constituted, $date_pt_panel_tested);
            $incomplete_kit_info = $this->check_kit_info($test_1_kit_name, $test_2_kit_name, $test_1_kit_lot_no, $test_2_kit_lot_no, $test_1_expiry_date, $test_2_expiry_date);
            $use_of_expired_kits = $this->check_expiry($date_pt_panel_tested, $test_1_expiry_date, $test_2_expiry_date, $test_3_expiry_date);
            $incomplete_results = $this->check_complete_results($pt_panel_1_test_1_results, $pt_panel_1_final_results, $pt_panel_2_test_1_results, $pt_panel_2_final_results, $pt_panel_3_test_1_results, $pt_panel_3_final_results, $pt_panel_4_test_1_results, $pt_panel_4_final_results, $pt_panel_5_test_1_results, $pt_panel_5_final_results, $pt_panel_6_test_1_results, $pt_panel_6_final_results);
            $incorrect_results = $this->check_correct_results($pt_panel_1_final_results, $pt_panel_2_final_results, $pt_panel_3_final_results, $pt_panel_4_final_results, $pt_panel_5_final_results, $pt_panel_6_final_results, $ex_1, $ex_2, $ex_3, $ex_4, $ex_5, $ex_6);
            $unsatisfactory = $this->check_satisfaction($incorrect_results);
            $invalid_results = $this->check_validity($pt_panel_1_test_1_results, $pt_panel_1_test_2_results, $pt_panel_1_test_3_results, $pt_panel_1_final_results, $pt_panel_2_test_1_results, $pt_panel_2_test_2_results, $pt_panel_2_test_3_results, $pt_panel_2_final_results, $pt_panel_3_test_1_results, $pt_panel_3_test_2_results, $pt_panel_3_test_3_results, $pt_panel_3_final_results, $pt_panel_4_test_1_results, $pt_panel_4_test_2_results, $pt_panel_4_test_3_results, $pt_panel_4_final_results, $pt_panel_5_test_1_results, $pt_panel_5_test_2_results, $pt_panel_5_test_3_results, $pt_panel_5_final_results, $pt_panel_6_test_1_results, $pt_panel_6_test_2_results, $pt_panel_6_test_3_results, $pt_panel_6_final_results);
            $wrong_algorithm = $this->check_algorithm($pt_panel_1_test_1_results, $pt_panel_1_test_2_results, $pt_panel_1_test_3_results, $pt_panel_1_final_results, $pt_panel_2_test_1_results, $pt_panel_2_test_2_results, $pt_panel_2_test_3_results, $pt_panel_2_final_results, $pt_panel_3_test_1_results, $pt_panel_3_test_2_results, $pt_panel_3_test_3_results, $pt_panel_3_final_results, $pt_panel_4_test_1_results, $pt_panel_4_test_2_results, $pt_panel_4_test_3_results, $pt_panel_4_final_results, $pt_panel_5_test_1_results, $pt_panel_5_test_2_results, $pt_panel_5_test_3_results, $pt_panel_5_final_results, $pt_panel_6_test_1_results, $pt_panel_6_test_2_results, $pt_panel_6_test_3_results, $pt_panel_6_final_results, $kit_1, $kit_2);
            $overall = $this->check_overall($dev_from_procedure, $incomplete_other_info, $incomplete_kit_info, $use_of_expired_kits, $incomplete_results, $incorrect_results, $unsatisfactory, $invalid_results, $wrong_algorithm);
            //  Update PT with the outcome of the algorithm.
            $pt->dev_from_procedure = $dev_from_procedure;
            $pt->incomplete_other_information = $incomplete_other_info;
            $pt->incomplete_kit_data = $incomplete_kit_info;
            $pt->use_of_expired_kits = $use_of_expired_kits;
            $pt->incomplete_results = $incomplete_results;
            $pt->incorrect_results = $incorrect_results;
            $pt->panel_result = $unsatisfactory;
            $pt->invalid_results = $invalid_results;
            $pt->wrong_algorithm = $wrong_algorithm;
            $pt->feedback = $overall;
            $pt->panel_status = Pt::EVALUATED;
            $pt->save();
        }
        return response()->json('Done.');
    }
}
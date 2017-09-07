<?php

namespace App\Http\Controllers\Auth;

use App\Notifications\SendVerificationCode;
use App\User;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use Illuminate\Foundation\Auth\RegistersUsers;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Notifications\Notification;

use App\Role;
use App\Facility;
use App\SubCounty;
use App\County;
use App\Program;
use App\Round;

use Mail;
use DB;
use Hash;
use Auth;
use App\Libraries\AfricasTalkingGateway as Bulk;
use Jenssegers\Date\Date as Carbon;

class RegisterController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Register Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles the registration of new users as well as their
    | validation and creation. By default this controller uses a trait to
    | provide this functionality without requiring any additional code.
    |
    */

    use RegistersUsers;

    /**
     * Where to redirect users after registration.
     *
     * @var string
     */
    protected $redirectTo = '/home';

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('guest');
    }

    /**
     * Get a validator for an incoming registration request.
     *
     * @param  array  $data
     * @return \Illuminate\Contracts\Validation\Validator
     */
    protected function validator(array $data)
    {
        return Validator::make($data, [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:6|confirmed',
        ]);
    }

    /**
     * Create a new user instance after a valid registration.
     *
     * @param  array  $data
     * @return User
     */
    protected function create(array $data)
    {
        return User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => bcrypt($data['password']),
            'verification_code' => Str::random(60),
            'status' => 0
        ]);
    }

    /**
     * Handle a registration request for the application.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function register(Request $request)
    {
        $usr = NULL;
        $validator = $this->validate($request, [
            'email' => 'required|unique:users,email',
            'phone' => 'required|unique:users,phone',
        ]);
        $now = Carbon::now('Africa/Nairobi');
        //  Prepare to save user details
        //  Check if user exists
        $userId = User::idByEmail($request->email);
        if(!$userId)
            $userId = User::idByUsername($request->username);
        if(!$userId)
        {
            $user = new User;
            $user->name = $request->surname." ".$request->fname." ".$request->oname;
            $user->gender = $request->gender;
            $user->email = $request->email;
            $user->phone = $request->phone;
            $user->address = $request->address;
            $user->username = $request->email;
            $user->password = Hash::make(User::DEFAULT_PASSWORD);
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
        DB::table('role_user')->insert(['user_id' => $userId, 'role_id' => $roleId, 'tier' => $facilityId, 'program_id' => $request->program, 'designation' => $request->designation]);
        /*
        *  Do SMS Verification for phone number
        */
        //  Bulk-sms settings
        $api = DB::table('bulk_sms_settings')->first();
        $username   = $api->code;
        $apikey     = $api->api_key;
        //  Remove beginning 0 and append +254
        $phone = ltrim($user->phone, '0');
        $recipient = "+254".$phone;
        // Generate code and store it in the database then send to participant
        $token = mt_rand(100000, 999999);
        $user->sms_code = $token;
        $user->save();
        $message    = "Your Verification Code is: ".$token;
        // Create a new instance of our awesome gateway class
        $gateway    = new Bulk($username, $apikey);
        try 
        { 
            // Specified sender-id
            $from = $api->code;
            // Send message
            $result = $gateway->sendMessage($recipient, $message);
        }
        catch ( AfricasTalkingGatewayException $e )
        {
            // echo "Encountered an error while sending: ".$e->getMessage();
            DB::table('role_user')->where('user_id', $user->id)->forceDelete();
            $user->forceDelete();
            abort(500, 'Encountered an error while sending verification code. Please try again later.');
        }
        try
        {
            //  Do Email verification for email address
            $user->email_verification_code = Str::random(60); 
            $user->save();
            
            event(new Registered($usr = $user));

        }
        catch(Exception $e)
        {
            DB::table('role_user')->where('user_id', $user->id)->forceDelete();
            $user->forceDelete();
            abort(500, 'Encountered an error while sending verification code. Please try again later.');
        }

        /*return $this->registered($request, $user)
            ?: redirect($this->redirectPath());*/
        return $this->registered($request, $user)
            ?: redirect('/2fa');
    }

    /**
     * The user has been registered.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  mixed  $user
     * @return mixed
     */
    protected function registered(Request $request, $user)
    {
        $user->notify(new SendVerificationCode($user));
    }

    public function verify($code)
    {
        $user = User::whereEmailVerificationCode($code)->first();

        if(!$user)
        {
            return redirect('/login')->with('error', '<strong>Invalid Code</strong>: Your verification code has been expired or invalid. <a href="#">Resend</a> verification code to my email.');
        }

        $user->status = 1;

        $user->verification_code = null;

        if($user->save())
        {
            return redirect('/login')->with('message', 'Email Verification Successful. Please login using your credentials.');
        }

    }

    public function resend($request)
    {
        if(strlen($request) < 10 || strlen($request) > 10)
            return response()->json(["error" => "Enter a valid phone number."]);
        $phone = ltrim($request, '0');
        $recipient = "+254".$phone;
        $user = User::withTrashed()->where('phone', 'LIKE', '%'.$phone.'%')->first();
        if(!$user)
            return response()->json(["error" => "Phone number not found."]);
        //  Bulk-sms settings
        $api = DB::table('bulk_sms_settings')->first();
        $username   = $api->username;
        $apikey     = $api->api_key;
        $token = mt_rand(100000, 999999);
        $user->sms_code = $token;
        $user->save();
        $message    = "Your Verification Code is: ".$token;
        // Create a new instance of our awesome gateway class
        $gateway    = new Bulk($username, $apikey);
        try 
        { 
            // Specified sender-id
            $from = $api->code;
            // Send message
            $result = $gateway->sendMessage($recipient, $message);
        }
        catch (\AfricasTalkingGatewayException $e )
        {
            // echo "Encountered an error while sending: ".$e->getMessage();
            return response()->json(["error" => "'Encountered an error while sending verification code. Please try again later."], 500);
        }
        try
        {
            //  Do Email verification for email address
            $user->email_verification_code = Str::random(60);
            $user->save();
            /*$usr = $user->toArray();

            Mail::send('auth.verification', $usr, function($message) use ($usr) {
                $message->to($usr['email']);
                $message->subject('National HIV PT - Email Verification Code');
            });*/

            event(new Registered($usr = $user));

            //$this->guard()->login($user);
        }
        catch(\Exception $e)
        {
            return response()->json(["error" => "'Encountered an error while sending verification code. Please try again later."]);
        }
    }
    /**
     * Redirect user for SMS verification
     *
     * @param  array  $data
     * @return User
     */
    protected function twoFa()
    {
        return view('auth.2fa');
    }
}
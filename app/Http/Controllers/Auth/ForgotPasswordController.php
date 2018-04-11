<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\User;
use Illuminate\Foundation\Auth\SendsPasswordResetEmails;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use App\Libraries\AfricasTalkingGateway as Bulk;
use App\SmsHandler;


class ForgotPasswordController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Password Reset Controller
    |--------------------------------------------------------------------------
    |
    | This controller is responsible for handling password reset emails and
    | includes a trait which assists in sending these notifications from
    | your application to your users. Feel free to explore this trait.
    |
    */

    use SendsPasswordResetEmails;

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('guest');
    }

    public function sendResetLinkEmail(Request $request)
    {

        $this->validate($request, ['uid' => 'required|numeric|min:1|exists:users,uid']);

        $user = User::where('uid',$request->get('uid'))->first();
        $phoneNumber = $user->phone;

        $token = mt_rand(100000, 999999);
        $user->sms_code = $token;
        $user->save();
        $message    = "Your Password Reset Verification Code is: ".$token;
        try
        {
            $smsHandler = new SmsHandler();
            $smsHandler->sendMessage($user->phone, $message);
        }
        catch ( \Exception $e )
        {
            return $this->sendResetLinkFailedResponse($request, $e->getMessage());
        }

//        // We will send the password reset link to this user. Once we have attempted
//        // to send the link, we will examine the response then see the message we
//        // need to show to the user. Finally, we'll send out a proper response.
//        $response = $this->broker()->sendResetLink(
//            $request->only('uid')
//        );

        return redirect('/password/code');

//        return $response == Password::RESET_LINK_SENT
//            ? $this->sendResetLinkResponse($response)
//            : $this->sendResetLinkFailedResponse($request, $response);
    }

    protected function sendResetLinkFailedResponse(Request $request, $response)
    {
        return back()->withErrors(
            ['uid' => trans($response)]
        );
    }

    public function codeVerify(Request $request)
    {

        return view('auth.passwords.code');
    }
}
<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Mail;

use App\Models\User;
use App\Models\UserLog;
use App\Models\Organization;

use App\Mail\Notify;

class LoginController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Login Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles authenticating users for the application and
    | redirecting them to your home screen. The controller uses a trait
    | to conveniently provide its functionality to your applications.
    |
    */

    use AuthenticatesUsers {
        sendFailedLoginResponse as superSendFailedLoginResponse;
    }

    /**
     * Where to redirect users after login.
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
        parent::__construct();
        $this->middleware('guest')->except('logout');
    }

    protected function authenticated(Request $request, User $user)
    {
        $user->loginCount += 1;
        $user->lastLogin = now();
        $user->save();
		
		// Insert this login into the UserLog table
		UserLog::create([
		  'userId' => $user->id,
          'event' => 'Login',
          'ip' => $request->ip()
		]);
    }

    public function sendFailedLoginResponse(Request $request)
    {
        $user = User::where('email', $request->email)->first();
        if (!is_null($user)) {
            UserLog::create([
                'userId' => $user->id,
                'event' => 'Login failed',
                'ip' => $request->ip()
            ]);
            
            $organization = Organization::find($user->organizationId);
            if (isset($organization)) {
                $link = url('/admin/activity');
                $text = "We detected a failed login for " . $user->email . " ({$user->name})";
                $text .= "<br>Click the link below to log in and review the logs";
                $text .= "<br><a href='{$link}'>{$link}</a>";
                $from = $user->email;
                $subject = "Failed login detected";
                
                try {
                    Mail::to($organization->contactEmail)
                        ->queue(new Notify($from, $subject, $text));
                } catch (\Exception $e) {}
            }
        }
            
        $this->superSendFailedLoginResponse($request);
    }
}

<?php

namespace App\Listeners;

use Illuminate\Auth\Events\Login;
use Illuminate\Http\Request;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;

use Log;
use App\Models\UserLog;
use App\Models\User;

class LogSuccessfulLogin
{
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct(Request $request)
    {
			$this->request = $request;
    }

    /**
     * Handle the event.
     *
     * @param  Login  $event
     * @return void
     */
    public function handle(Login $event)
    {
		$user = $event->user;
		$request = $this->request;
		
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
}

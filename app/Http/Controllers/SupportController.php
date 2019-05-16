<?php
namespace App\Http\Controllers;

use App\Http\Controllers\Controller;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Log;

use App\Models\User;
use App\Models\UserLog;

use Auth;
use phpDocumentor\Reflection\DocBlock\Tags\Param;
use Storage;

class SupportController extends Controller
{
	/**
	 * Create a new controller instance.
	 *
	 * @return void
	 */
	function __construct()
	{
        parent::__construct();
		$this->middleware('auth');
	}

	function __destruct()
	{
	}

	/**
	 *
	 */
	public function index()
	{
		return view('support.index');
	}

}

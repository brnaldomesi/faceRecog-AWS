<?php
namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;

use Illuminate\Http\Request;

use App\Models\User;
use App\Models\Organization;
use App\Models\Stat;

use App\Http\Requests\CasesCreate;

use Auth;
use Hash;

class OrganizationController extends Controller
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

	public function index()
	{
		$organizations = Organization::all();
		$admins = $organizations->map(function($organ) {
			foreach($organ->users as $user) {
				if ($user->permission->isAdmin()) {
					return $user; 
				}
			}
		});
		return view('organization.index', compact('organizations', 'admins'));
	}

	/**
	 * USER CLICKED ENROLL SCREEN
	 *
	 * @return void
	 */
	public function new()
	{
		return view('organization.new');
	}

	/**
	 * Creates new organization
	 *
	 * @return void
	 */

	public function create(Request $request)
	{
		$request->validate([
	        'name' => 'required|unique:organizations',
	        'email' => 'required|email|unique:users',
	        'password' => 'required|confirmed|min:6'
	    ]);

		$newOrganization = Organization::create([
			'name' => $request->name,
			'account' => $request->account,
			'contactName' => $request->adminName,
			'contactEmail' => $request->email,
			'contactPhone' => $request->contactPhone,
			'aws_collection_male_id' => '',
			'aws_collection_female_id' => '',
			'aws_collection_cases_id' => ''
		]);

		$organId = $newOrganization->id;
		
		$newUser = new User;
		$newUser->name = $request->adminName;
		$newUser->email = $request->email;
		$newUser->organizationId = $organId;
    	$newUser->userGroupId = 1;
        $newUser->password = Hash::make($request->password);
	    $newUser->save();

	    $newStat = new Stat;
	    $newStat->organizationId = $organId;
	    $newStat->searches = 0;
	    $newStat->save();

	    return redirect()->route('organization')->with('isOrgCreated', true);
	}

}

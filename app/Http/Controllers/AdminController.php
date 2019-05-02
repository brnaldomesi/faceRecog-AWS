<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

use App\Models\Organization;
use App\Models\FacesetSharing;
use App\Models\User;
use App\Models\UserLog;

use App\Mail\Notify;

use Auth;
use Hash;
use DB;


class AdminController extends Controller
{
    function __construct()
    {
        parent::__construct();
    }

    public function index(Request $request)
    {		
		return view('admin.index');
    }

    public function user(User $user)
    {
    	return view('admin.user', [
    		'user' => $user,
    		'organization' => Organization::find($user->organizationId)->name
    	]);
    }

    public function createForm()
    {
        return view('admin.user', [
            'organization' => Organization::find(Auth::user()->organizationId)->name
        ]);
    }
	
	public function activityLog()
	{
		// Build our list of activity log for this organization
		$activity = UserLog::whereHas('user', function ($query) {
			$query->where('organizationId', Auth::user()->organizationId);
		})->with('user')->orderBy('updated_at', 'desc')->get();

		return view('admin.activity',compact('activity'));
	}
	
	public function manageUsers()
    {
		// Build our list of users for this organization
		$users = User::orderBy('name', 'asc')
			->where('organizationId', Auth::user()->organizationId)
			->get();
        return view('admin.users-manage',compact('users'));
    }
	
	public function sharing(Request $request)
	{
	    if (Organization::find($request->organization)) {
			switch ($request->action_type) {
				case 'action-apply':
					$sharing = FacesetSharing::where([
						['organization_requestor', $request->user()->organizationId],
						['organization_owner', $request->organization]
					])
					->orWhere([
						['organization_owner', $request->user()->organizationId],
						['organization_requestor', $request->organization]
					])
					->first();

					if($sharing) {
						// If previous sharing request is in Pending or Active status, we return. 
						// Otherwise, we change status to Pending
						if($sharing->status == 'PENDING' || $sharing->status == 'ACTIVE')
							break;
					} else {
						// create a new FaceSetSharing request if no sharing request exists
						$sharing = new FacesetSharing();
					}
					$sharing->organization_requestor = $request->user()->organizationId;
					$sharing->organization_owner = $request->organization;
					$sharing->status = 'PENDING';
					$sharing->save();
					
					$organization_requestor = Organization::find($request->user()->organizationId);
					$organization_owner = Organization::find($request->organization);
					if (isset($organization_owner)) {
						$link = url('/admin/sharing');
						$text = $organization_requestor->name . " has requested to share mugshot data with you. To approve or deny this request, click the link below to log in.";
						$text .= "<br><br>Time requested: " . now();
						$text .= "<br><br><a href='{$link}'>{$link}</a><br><br>";
						$text .= "This email address is not monitored.  Please do not reply.";
						$from = "notifications@afrengine.com";
						$subject = "AFR Engine :: Request to share mugshot data from " . $organization_requestor->name;
						
						try {
							Mail::to($organization_owner->contactEmail)
								->queue(new Notify($from, $subject, $text));
						} catch (\Exception $e) {}
					}
					break;

				case 'action-approve':
				case 'action-decline':
					$sharing = FacesetSharing::where([
						['organization_owner', Auth::user()->organizationId],
						['organization_requestor', $request->organization]
					])->first();
					if ($sharing) {
						$sharing->status = $request->action_type == 'action-approve' ? 'ACTIVE' : 'DECLINED';
						$sharing->save();
					}
					break;
			}
		}
		return redirect()->route('admin.sharing.show');
	}

	public function sharingForm()
	{
		// Get list of Organizations that User's organization has requested to share data with
		$sharing_out = FacesetSharing::with('owner')
			->where('organization_requestor', Auth::user()->organizationId)
			->where('status', '<>', 'ACTIVE')
			->get();

		// Get list of Organizations that have requested data sharing to User's organization
		$sharing_in = FacesetSharing::with('requestor')
			->where('organization_owner', Auth::user()->organizationId)
			->where('status', '<>', 'ACTIVE')
			->get();

		// Get list of Organizations that share Mugshot data with the User's organization 
		$sharing_approved = FacesetSharing::with(['owner', 'requestor'])
			->where('status', '=', 'ACTIVE')
            ->where(function ($query) {
                $query->where('organization_owner', Auth::user()->organizationId)
					  ->orWhere('organization_requestor', Auth::user()->organizationId);
            })
			->orderBy('id', 'asc')
			->get();
		
		// Get list of other organizations that are available for data sharing
		$sharing_all = FacesetSharing::where(function ($query) {
                $query->where('organization_owner', Auth::user()->organizationId)
					  ->orWhere('organization_requestor', Auth::user()->organizationId);
            })
			->get();

		$organizations_sharing_existing = [Auth::user()->organizationId];
		foreach ($sharing_all as $sharing) {
			if($sharing->organization_owner != Auth::user()->organizationId)
            	$organizations_sharing_existing[] = $sharing->organization_owner;
            if($sharing->organization_requestor != Auth::user()->organizationId)
            	$organizations_sharing_existing[] = $sharing->organization_requestor;
        }

		$organizations_sharing_available = Organization::whereNotIn('id', $organizations_sharing_existing)
			->get();
					
		return view('admin.sharing',compact('sharing_in', 'sharing_out', 'sharing_approved', 'organizations_sharing_available'));
	}
	
	public function sharingedit($id)
	{
		// Get the info for this sharing request
		//$sharing = DB::table('faceset_sharing')
		//	->where('id',$request->id)
		//	->get();
	
		// Get list of Organizations that are eligible to data share
		$organization = DB::table('organizations')
			->where('id',$id)
			->get();
		
		return view('admin.sharingedit',compact('organization'));
		//return view('admin.sharingedit',compact('sharing'));
	}

    public function delete(User $user)
    {
        if ($user->id != Auth::user()->id) {
            $user->delete();
        }
        return redirect()->route('admin');
    }

    public function create(Request $request)
    {
        $request->validate([
            'name' => 'required',
            'email' => 'required|email|unique:users',
            'password' => 'confirmed'
        ]);

        $user = new User;
        $user->name = $request->name;
        $user->email = $request->email;
        $user->organizationId = Auth::user()->organizationId;
        $user->userGroupId = 2;

        if (empty($request->password)) {
            $user->password = Hash::make('123456789');
        } else {
            $user->password = Hash::make($request->password);
        }
        $user->save();

        return redirect()->route('admin');
    }

    public function update(Request $request, User $user)
    {
    	$request->validate([
    		'name' => 'required',
    		'email' => 'required|unique:users,email,' . $user->id,
    		'password' => 'confirmed'
    	]);

    	$user->name = $request->name;
    	$user->email = $request->email;

    	if (Organization::find($request->organizationId)) {
			$user->organizationId = $request->organizationId;
    	}

    	if ($request->password) {
    		 $user->password = Hash::make($request->password);
    	}
    	$user->save();

    	return redirect()->route('admin.id.show', $user);
    }
}

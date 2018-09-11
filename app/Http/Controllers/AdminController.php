<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Models\Organization;
use App\Models\User;
use App\Models\UserLog;

use Auth;
use Hash;
use DB;


class AdminController extends Controller
{
    //

	public function __construct()
	{
/*		dd(Auth::user());
		if (!Auth::user()->permission->can_edit_all_users) {
			return abort(401);
		}*/
	}

    public function index(Request $request)
    {
		// Build our list of users for this organization
		$users = DB::table('users')
			->orderBy('name','asc')
			->where('organizationId',Auth::user()->organizationId)
			->get();
			
		// Build our list of activity log for this organization
		$activity = DB::table('user_logs')
			->orderBy('date_time','desc')
			->join('users','userId', '=', 'users.id')
			->get();
		
		return view('admin.index',compact('users','activity'));
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
		$activity = DB::table('user_logs')
			->orderBy('date_time','desc')
			->join('users','userId', '=', 'users.id')
			->get();
			
		return view('admin.activity',compact('activity'));
	}
	
	public function manageUsers()
    {
		// Build our list of users for this organization
		$users = DB::table('users')
			->orderBy('name','asc')
			->where('organizationId',Auth::user()->organizationId)
			->get();
			
        return view('admin.users-manage',compact('users'));
    }
	
	public function sharing()
	{
		// Get list of Organizations that the User's organization has some sort of sharing status with
		$sharing = DB::table('faceset_sharing')
			->where('organization_owner',Auth::user()->organizationId)
			->get();
		
		// Get list of Organizations that are eligible to data share
		$organizations = DB::table('organizations')
			->where('id','<>',Auth::user()->organizationId)
			->get();
			
		return view('admin.sharing',compact('sharing','organizations'));
	}
	
	public function sharingedit($id)
	{
		// Get the info for this sharing request
		//$sharing = DB::table('faceset_sharing')
//			->where('id',$request->id)
	//		->get();
	
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
            'email' => 'required|unique:users',
            'password' => 'confirmed'
        ]);

        $user = new User;
        $user->name = $request->name;
        $user->email = $request->email;
        $user->organizationId = Auth::user()->organizationId;
        $user->userGroupId = 2;

        if (empty($user->password)) {
            $user->password = Hash::make('123456789');
        } else {
            $user->password = $request->password;
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

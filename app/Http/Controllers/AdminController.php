<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Models\Organization;
use App\Models\User;

use Auth;
use Hash;


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
    	return view('admin.index')->with('users', User::all());
    }

    public function user(User $user)
    {
    	$organ = Organization::all(['id', 'name'])
    		->mapWithKeys(function ($item) {
    		return [$item['id'] => $item['name']];
    	});

    	return view('admin.user', [
    		'user' => $user,
    		'organization' => $organ
    	]);
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

<?php

/*
Home Screen once the user is logged in

*/

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Models\Organization;
use App\Models\Stat;
use App\Models\Cases;
use App\Models\FacesetSharing;
use App\Models\Face as FaceModel;

use Auth;

class HomeController extends Controller
{
    /**
     * Create a new controller instance.
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Show the application dashboard.
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
		// Get the OrganizationID for the logged in user
        $isSuperAdmin = Auth::user()->permission->isSuperAdmin();

        if($isSuperAdmin) {
            $organizationCount = Organization::count();
            $faceCount = FaceModel::count();
        }
        else
        {
            $organizationId = Auth::user()->organizationId;
            
            // Get the User ID for the logged in user
            $userId = Auth::user()->id;
            
            // Retrieve the # of Detected faces that are in the system for this Organization
            $faces = Organization::find($organizationId)->faces;
            $facesCount = $faces->count();
            
            // Retrieve the # of Active cases for the logged in user
            $cases = Cases::where('userId',$userId)->get();
            $caseCount = $cases->count();
            
            // Retrieve the # of searches that have been performed by this organization
            $searchCount = Organization::find($organizationId)->stat->searches;
            
            // Retrieve the # of pending applications that was sent to this user
            $appCount = FacesetSharing::where([
                ['organization_requestor', Auth::user()->organizationId],
                ['status', 'PENDING']
            ])->count();
        }
        
        // Send the totals back to the home view so we can display the data to the user
        return view('home', compact('caseCount', 'facesCount', 'searchCount', 'appCount', 'organizationCount', 'faceCount'));
    }
}

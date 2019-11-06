<?php

/*
Home Screen once the user is logged in

*/

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

use App\Models\Organization;
use App\Models\Stat;
use App\Models\Cases;
use App\Models\CaseSearch;
use App\Models\FacesetSharing;
use App\Models\Face as FaceModel;
use App\Models\FaceTmp;
use App\Models\User;

use Auth;

class HomeController extends Controller
{
    /**
     * Create a new controller instance.
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
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
			$faceQue = FaceTmp::count();
			$searchCount = CaseSearch::count();
            $caseCount = Cases::count();
			$solvedCaseCount = Cases::where('status','=','SOLVED')->get()->count();
			$todaysUsers = User::whereDate('lastlogin','=',Carbon::today())->get()->count();
			
			$ackTerms = 'true';
        }
        else
        {
            $organizationId = Auth::user()->organizationId;
			
			$orgTotalCases = Cases::where('organizationId',$organizationId)->get()->count();
			$orgCasesSolved = Cases::where('organizationId',$organizationId)
									->where('status','=','SOLVED')->get()->count();
			
            // Get the User ID for the logged in user
            $userId = Auth::user()->id;
            
            // Retrieve the # of Detected faces that are in the system for this Organization
            //$faces = Organization::find($organizationId)->faces;
            //$facesCount = $faces->count();
            
            // Retrieve the # of Active cases for the logged in user
            $cases = Cases::where('userId',$userId)->get();
            $caseCount = $cases->count();
            
            // Retrieve the # of searches that have been performed by this organization
            //$searchCount = Organization::find($organizationId)->stat->searches;
            
            // Retrieve the # of pending applications that was sent to this user
            $appCount = FacesetSharing::where([
                ['organization_owner', Auth::user()->organizationId],
                ['status', 'PENDING']
            ])->count();
			
			// Check if the user has seen the facial recognition terms of use popup
			if (session()->get('ackTerms') == 'true') {
				$ackTerms = 'true';
			} else {
				$ackTerms = 'false';
				session()->put('ackTerms','true');
			}
        }
        
        // Send the totals back to the home view so we can display the data to the user
        //return view('home', compact('caseCount', 'facesCount', 'searchCount', 'appCount', 'organizationCount', 'faceCount'));
		
		return view('home', compact('caseCount', 'appCount', 'organizationCount', 'faceCount','faceQue','todaysUsers','searchCount','solvedCaseCount','orgTotalCases','orgCasesSolved','ackTerms'));
    }
}

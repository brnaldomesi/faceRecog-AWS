<?php

/*
Home Screen once the user is logged in

*/

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Auth;
use App\Organization;
use App\Stat;

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
        $organizationId = Auth::user()->organizationId;
		
		// Retrieve the # of Detected faces that are in the system for this Organization
        $faces = Organization::find($organizationId)->faces;
        $facesCount = $faces->count();
		
		// Calculate how many of the detected faces have shown up in searches by this organization
        $matchedFacesCount = $faces->where('faceMatches', '<>', 0)->count();
        
		if($matchedFacesCount == 0) 
			$matchedFacesCount = 1;
		
        $faceMatchesCount = $faces->sum('faceMatches') / $matchedFacesCount;
        
		// Retrieve the # of searches that have been performed by this organization
		$searchCount = Organization::find($organizationId)->stat->searches;
		
		// Send the totals back to the home view so we can display the data to the user
        return view('home',['facesCount' => $facesCount, 'searchCount' => $searchCount, 'faceMatchesCount' => $faceMatchesCount]);
    }
}

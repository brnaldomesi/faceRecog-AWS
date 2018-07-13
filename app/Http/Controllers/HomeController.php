<?php


namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Auth;
use App\Organization;
use App\Stat;

class HomeController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $organizationId = Auth::user()->organizationId;
        $faces = Organization::find($organizationId)->faces;
        $facesCount = $faces->count();
        $matchedFacesCount = $faces->where('faceMatches', '<>', 0)->count();
        if($matchedFacesCount == 0)
            $matchedFacesCount = 1;
        $faceMatchesCount = $faces->sum('faceMatches') / $matchedFacesCount;
        $searchCount = Organization::find($organizationId)->stat->searches;
        return view('home',['facesCount' => $facesCount, 'searchCount' => $searchCount, 'faceMatchesCount' => $faceMatchesCount]);
    }
}

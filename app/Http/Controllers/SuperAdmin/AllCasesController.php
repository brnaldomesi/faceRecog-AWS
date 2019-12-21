<?php
namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Log;

use App\Models\User;
use App\Models\Cases;
use App\Models\CaseSearch;
use App\Models\CaseMatch;
use App\Models\Organization;
use App\Models\Image;

use Auth;
use phpDocumentor\Reflection\DocBlock\Tags\Param;
use Storage;

class AllCasesController extends Controller
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
		$cases = Cases::orderBy('created_at','desc')->get();		
		$solved = Cases::where('status','=','SOLVED')->get();
		$cases->solvedCases = $solved->count();
		
		return view('allcases.index')->with('cases', $cases);
	}

	public function orgIndex(Organization $org)
	{
		$cases = $org->cases()
			->orderBy('created_at', 'desc')
			->get();
		
		return view('allcases.orgindex')->with('cases', $cases)->with('org', $org);
	}

	public function cases(Organization $org, Cases $cases)
	{
	    return view('allcases.cases', [
			'cases' => $cases,
			'search_history' => $cases->caseSearches()->with('image')->orderBy('created_at', 'asc')->get()
		]);
	}

	public function imagelist(Request $request, Organization $org, Cases $cases)
	{
		if (!is_null($request->delete)) {
			$image = Image::find($request->delete);
			$this->authorize('delete', $image);

			if (true) {
				if (Storage::exists($image->file_path)) {
					Storage::delete($image->file_path);
				}
				if (Storage::exists($image->thumbnail_path)) {
					Storage::delete($image->thumbnail_path);
				}
			}
			$image->delete();
		}

		$result = $cases->images->map(function ($item, $key) {
			
			if (!is_null($item->lastSearched)) {
				$date = date_create($item->lastSearched);
				$date = date_format($date,"m/d/Y H:i:s");
			} else {
				$date = "";
			}
			
			$caseMatches = CaseMatch::where('source_imageId','=',$item->id)->first();
			
			if (isset($caseMatches)) {
				$caseMatchId = $caseMatches->id;
			} else {
				$caseMatchId = '';
			}
			
			return [
				env('AWS_S3_REAL_OBJECT_URL_DOMAIN').'storage/case/images/'.$item->filename_uploaded, //asset($item->file_url),
                env('AWS_S3_REAL_OBJECT_URL_DOMAIN').'storage/case/thumbnails/'.$item->filename_uploaded,//asset($item->thumbnail_url),
				$item->gender,
				$date,
				$item->id,
				$caseMatchId
			];
		});
		return response()->json(['data' => $result]);
	}
}

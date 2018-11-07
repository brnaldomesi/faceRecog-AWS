<?php
namespace App\Http\Controllers;

use App\Http\Controllers\Controller;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Input;

use App\Models\Face as FaceModel;
use App\Models\User;
use App\Models\Faceset;
use App\Models\Cases;
use App\Models\Image;
use App\Models\CaseSearch;
use App\Models\Organization;

use App\Http\Requests\CasesCreate;
use App\Http\Requests\CasesUpdate;

use App\Utils\UploadHandler;
use App\Utils\ImageResize;
use App\Utils\FaceSearch;
use App\Utils\Facepp;

use Auth;
use Storage;

class CaseController extends Controller
{
	public $facepp;
	/**
	 * Create a new controller instance.
	 *
	 * @return void
	 */
	function __construct()
	{
		$this->middleware('auth');
		$this->facepp = new Facepp();
		$this->facepp->api_key = config('face.providers.face_plus_plus.api_key');
		$this->facepp->api_secret = config('face.providers.face_plus_plus.api_secret');
	}

	function __destruct()
	{
		unset($this->facepp);
	}

	/**
	 * Returns the available facesets for the organization
	 *
	 * NOTE: We will need to expand this to check what other organization face sets are accessible and then add them to this list
	 *
	 * @return void
	 */
	public function index()
	{
		$cases = Auth::user()->cases()
			->orderBy('created_at', 'asc')
			->get();
		
		return view('cases.index')->with('cases', $cases);
	}

	/**
	 * USER CLICKED ENROLL SCREEN
	 *
	 * @return void
	 */
	public function createForm()
	{
		return view('cases.create');
	}


	public function cases(Cases $cases)
	{
		return view('cases.cases', [
			'cases' => $cases,
			'search_history' => $cases->caseSearches()->with('image')->orderBy('created_at', 'asc')->get()
		]);
	}


	/**
	 * Creates new case
	 *
	 * @return void
	 */

	public function create(CasesCreate $request)
	{
		$user = Auth::user();

		$cases = new Cases;
		$cases->organizationId = $user->organizationId;
		$cases->userId = $user->id;
		$cases->status = 'ACTIVE';
		$cases->caseNumber = $request->caseNumber;
		$cases->type = $request->type;
		$cases->save();

		return redirect()->route('cases.id.show', $cases);
	}

	public function update(CasesUpdate $request, Cases $cases)
	{
		$cases->caseNumber = $request->caseNumber;
		$cases->type = $request->type;
		if (!is_null($request->status)) {
			$cases->status = $request->status;
		}
		if ($request->status == 'CLOSED') {
			$cases->dispo = $request->dispo;
		}
		$cases->save();

		return redirect()->route('cases.id.show', $cases);
	}

	public function addImage(Request $request, Cases $cases)
	{
		$file = null;

		if ($cases->status != 'ACTIVE') {
			return abort(403);
		}

		if ($request->hasFile('files')) {
			$file = count($request->file('files')) > 0 ? ($request->file('files'))[0] : null;
		}

		if ($file == null || !$file->isValid()) {
			return abort(500);
		}

		$name_client = $file->getClientOriginalName();
		$name_upload = str_random(15) . "." . $file->guessClientExtension();

		$image = new Image;
		$image->caseId = $cases->id;
		$image->filename = $name_client;
		$image->filename_uploaded = $name_upload;
		$image->gender = $request->gender;
		$image->uploaded = now();
		$image->lastSearched = null;
		$image->save();

		if (! $file->storeAs('public/case/images', $name_upload)) {
			return abort(500);
		}

		Storage::makeDirectory('public/case/thumbnails');
		ImageResize::work('../storage/app/' . $image->file_path, 256, 0, '../storage/app/' . $image->thumbnail_path);

		$result = array(
			'deleteType'    => 'DELETE',
			'deleteUrl'     => asset('foobar'),
			'name'          => $name_client,
			'size'          => $file->getClientSize(),
			'type'          => $file->getClientMimeType(),
			'thumbnailUrl'  => asset($image->thumbnail_url),
			'url'           => asset($image->file_url)
		);
		
		return response()->json(['files' => [$result]]);
	}

	public function imagelist(Request $request, Cases $cases)
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
			return [
				asset($item->file_url),
				asset($item->thumbnail_url),
				$item->filename,
				$item->lastSearched,
				$item->id
			];
		});
		return response()->json(['data' => $result]);
	}

	public function search(Request $request)
	{
		if (is_null($request->image)) {
			return response('Incorrect parameter', 400);
		}
		$image = Image::find($request->image);
		if (is_null($image)) {
			return response('Incorrect parameter', 400);
		}

		$gender = $image->gender;
		$organ_id = Auth::user()->organizationId;
		$result = FaceSearch::search('../storage/app/' . $image->file_path, $organ_id, $gender);
		$image->lastSearched = now();
		$image->save();
		
		$search = CaseSearch::create([
			'organizationId' => $organ_id,
			'imageId' => $image->id,
			'searchedOn' => now(),
			'results' => $result
		]);

		if ($result['status'] == 204) {
		//	return response($result, 204);
		}
		
		return response()->json(
			array_merge($result, [
					'time' => date('Y-m-d h:m:s'),
					'history_no' => $search->id
				]
			)
		);
	}

	public function searchHistory(Request $request)
	{
		if (is_null($request->history)) {
			return response('Incorrect parameter', 400);
		}
		$search = CaseSearch::find($request->history);
		if (is_null($search)) {
			return response('Incorrect parameter', 400);
		}

		$result = $search['results'];

		if ($result['status'] == 204) {
		//	return response($result, 204);
		}
		
		return response()->json($result);
	}

}

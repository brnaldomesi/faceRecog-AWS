<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Mail;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

use App\Models\User;
use App\Models\Cases;
use App\Models\Image;
use App\Models\Faceset;
use App\Models\CaseSearch;
use App\Models\Organization;

use App\Utils\FaceSearch;
use App\Mail\Notify;


class SearchMug implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $case_list = array();
        // Checks all organizations for any ACTIVE cases that have Images that have not been searched for 30 days or more
        $images = Image::whereHas('cases', function ($query) {
            $query->where('status', 'ACTIVE');
        })
        ->where(function ($query) {
            $query->where('lastSearched', '<', Carbon::now()->subDays(30))
                ->orWhereNull('lastSearched');
        })
        ->get();
        
        foreach ($images as $image) {
            $file_path = '../storage/app/' . $image->file_path;
            $organ = $image->cases->organization;
            
            if (is_null($organ)) {
                continue;
            }
            $gender = $image->gender;
            $organ_id = $organ->id;
            
            // Search image only from facesets that were updated after last search date of the image by setting 5th parameter
            $result_new = FaceSearch::searchBySimilarityScore($file_path, $organ_id, $gender, 'CASE_SEARCH', $image->lastSearched);
            // Skip image for nothing new
            if (count($result_new['result']) == 0) {
                continue;
            }

            // Count newly detected image for case we come across
            if (!isset($case_list[$image->caseId])) {
                $case_list[$image->caseId] = 1;
            } else {
                $case_list[$image->caseId]++;
            }

            $result = $result_new;
            
            // Fetch existing search result for the image from CaseSearch table
            $result_orig_arr = CaseSearch::where('imageId', $image->id)->get();

            if(!$result_orig_arr->isEmpty()) {
              $result['result'] = [];
            }
             
            foreach ($result_new['result'] as $r) {
                if (count($r) > 0) {
                  foreach($result_orig_arr as $result_orig) {
                    if (!is_null($result_orig)) {
                        $result_orig = $result_orig['results'];
                    }
                    if ($result_new['status'] == 200 && !is_null($result_orig) && $result_orig['status'] == 200) {
                      $diff = [];
                      foreach ($result_orig['result'] as $orig) {
                          
                          $r = array_udiff($r, $orig,
                            function ($obj_a, $obj_b) {
                                        return strcmp(((object)$obj_a)->faceToken, ((object)$obj_b)->faceToken);
                            }
                          );
                      }
                    }
                  }
                }
                if(!empty($r)){
                    $r = array_values($r);
                    array_push($result['result'], $r);
                }
            }
            // Merge newly searched result with existing searched result

            // Update json result and image search date
            $image->lastSearched = now();
            $image->save();
            //CaseSearch::where('imageId', $image->id)->delete();
            if(!empty($result['result'])) {
                $search = CaseSearch::create([
                    'organizationId' => $organ_id,
                    'imageId' => $image->id,
                    'searchedOn' => now(),
                    'results' => $result
                ]);
            }
        }

        // Organize mail data
        $user_list = array();
        foreach ($case_list as $key => $count) {
            $user_id = Cases::find($key)->userId;
            $user_list[$user_id][$key] = $count;
        }

        $mail_list = array();
        foreach ($user_list as $user_id => $cases) {
            $user = User::find($user_id);
            if (!is_null($user)) {
                $mail = array('to' => $user->email, 'name' => $user->name, 'cases' => array());
                foreach ($cases as $case_id => $count) {
                    array_push($mail['cases'], ['id' => $case_id, 'name' => Cases::find($case_id)->caseNumber, 'count' => $count]);
                }
                array_push($mail_list, $mail);
            }
        }

        // Notify auto-search fact to user via mail
        foreach ($mail_list as $mail) {
            $text = $mail['name'] . ", we have automatically re-scanned some of your evidence photos and we found new similar faces.";
            $text .= "<br>Log in and review them to see if they match your suspects.<br>";
            
			foreach ($mail['cases'] as $c) {
                $link = url('cases/' . $c['id']);
                $text .= "<br>Case '" . $c['name'] . "' has " . $c['count'] . " new mug shot results.";
                $text .= "<br><a href='{$link}'>{$link}</a><br>";
            }
            $from = config('mail.username');
            $subject = "AFR Engine :: Your cases have new mugshots to review";

            Mail::to($mail['to'])
                ->queue(new Notify($from, $subject, $text));
        }
    }
}

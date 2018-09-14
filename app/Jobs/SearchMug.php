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
            $result_new = FaceSearch::search($file_path, $organ_id, $gender, 'CASE_SEARCH', $image->lastSearched);

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

            // Fetch existing search result for the image from CaseSearch table
            $result_orig = CaseSearch::where('imageId', $image->id)
                ->orderBy('updated_at', 'desc')
                ->first();

            if (!is_null($result_orig)) {
                $result_orig = $result_orig['results'];
            }
            $result = $result_new;

            // Merge newly searched result with existing searched result
            if ($result_new['status'] == 200 && !is_null($result_orig) && $result_orig['status'] == 200) {
                foreach ($result_orig['result'] as $orig) {
                    if (count($orig) > 0) {
                        $flag = true;
                        foreach ($result_new['result'] as $r) {
                            if (count($r) > 0 && $r[0]['facesetId'] == $orig[0]['facesetId']) {
                                $flag = false;
                                break;
                            }
                        }
                        if ($flag) {
                            array_push($result['result'], $orig);
                        }
                    }
                }
            }

            // Update json result and image search date
            $image->lastSearched = now();
            $image->save();
            // CaseSearch::where('imageId', $image->id)->delete();
            $search = CaseSearch::create([
                'organizationId' => $organ_id,
                'imageId' => $image->id,
                'searchedOn' => now(),
                'results' => $result
            ]);
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
            $text = $mail['name'] . ", We have worked hard to get updated search result for mug shot!";
            $text .= "<br>Please go see and look into who they are just now.<br>";
            foreach ($mail['cases'] as $c) {
                $link = url('cases/' . $c['id']);
                $text .= "<br>Case '" . $c['name'] . "' has new search result for " . $c['count'] . " mug shots.";
                $text .= "<br><a href='{$link}'>{$link}</a><br>";
            }
            $from = config('mail.username');
            $subject = "Found new mug";

            Mail::to($mail['to'])
                ->queue(new Notify($from, $subject, $text));
        }
    }
}

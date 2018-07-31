<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

use App\Models\Cases;

class CasesRequest extends FormRequest
{
    public function failedAuthorization()
    {
        return abort(401, 'Unauthorized');
    }

    public function rules()
    {
        return [
            'caseNumber' => 'required',
            'type' => 'required'
        ];
    }

    protected function authorizationCondition()
    {
        return Cases::where([
            ['caseNumber', $this->caseNumber],
            ['organizationId', $this->user()->organizationId],
            ['userId', $this->user()->id]
        ]);
    }

    protected function validate($validator, $duplicated)
    {
        if ($duplicated) {
            $validator->after(function ($validator) {
                $validator->errors()->add('caseNumber', 'Case # is duplicated. Try another one.');
            });
        }
    }
}

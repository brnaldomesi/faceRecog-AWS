<?php

namespace App\Http\Requests;

use App\Http\Requests\CasesRequest;


class CasesCreate extends CasesRequest
{
    public function authorize()
    {
        return $this->user()->can('create', 'App\Models\Cases');
    }

    public function withValidator($validator)
    {
        $this->validate($validator, $this->authorizationCondition()->count());
    }
}

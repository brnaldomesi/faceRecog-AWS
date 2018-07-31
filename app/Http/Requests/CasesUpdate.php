<?php

namespace App\Http\Requests;

use App\Http\Requests\CasesRequest;


class CasesUpdate extends CasesRequest
{
    public function authorize()
    {
        return $this->user()->can('update', $this->route('cases'));
    }

    public function withValidator($validator)
    {
        $this->validate($validator,
            $this->authorizationCondition()->where('id', '<>', $this->route('cases')->id)->count());
    }
}

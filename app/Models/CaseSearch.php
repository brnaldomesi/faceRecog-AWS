<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CaseSearch extends Model
{
    //

    protected $fillable = ['organizationId', 'imageId', 'searchedOn', 'results'];
	protected $casts = [
		'results' => 'array'
	];

    public function image()
    {
    	return $this->belongsTo('App\Models\Image', 'imageId');
    }

    public function getMatchCountAttribute()
    {
    	$c = 0;
    	$result = $this->results;

    	if (isset($result['result'])) {
	    	$t = $result['result'];
	    	for ($i = 0; $i < count($t); $i++) {
	    		$c += count($t[$i]);
	    	}
    	}
    	return $c;
    }
}

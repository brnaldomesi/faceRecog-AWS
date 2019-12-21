<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CaseMatch extends Model
{
    public $timestamps = false;
	
	protected $table = 'case_matches';
	
    protected $fillable = ['case_id', 'source_imageId', 'results', 'searchedOn'];
	protected $casts = [
		'results' => 'array'
	];
}
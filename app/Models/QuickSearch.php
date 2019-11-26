<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class QuickSearch extends Model
{
    //
	protected $table = 'quicksearch_history';
	
    protected $fillable = ['userid', 'reference', 'filename', 'results'];
	protected $casts = [
		'results' => 'array'
	];
}
<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Cases extends Model
{
	protected $table = 'cases';	// Had to define it because 'Case' was a reserved word.
}

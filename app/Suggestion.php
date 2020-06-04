<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Suggestion extends Model
{
	protected $table = 'suggestions';

	public function Location(){
		return $this->belongsTo('App\Location', 'location_id');
	}

	protected $fillable = [
		'name', 'surname', 'email', 'phone', 'location_id', 'suggestion'
	];
}

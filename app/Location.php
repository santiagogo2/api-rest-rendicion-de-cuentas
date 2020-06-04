<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Location extends Model
{
	protected $table = 'locations';

	public function Suggestions(){
		return $this->hasMany('App\Suggestion');
	}

	protected $fillable = [
		'name'
	];
}

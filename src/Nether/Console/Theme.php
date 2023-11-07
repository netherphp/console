<?php

namespace Nether\Console;

use Nether\Common;

class Theme {

	const
	Default = 'Default',
	Prime   = 'Primary',
	Accent  = 'Accent',
	OK      = 'OK',
	Error   = 'Error',
	Strong  = 'Strong',
	Muted   = 'Muted';

	const
	Types = [
		self::Default,
		self::Prime, self::Accent,
		self::OK, self::Error,
		self::Strong, self::Muted
	];

	////////////////////////////////////////////////////////////////
	////////////////////////////////////////////////////////////////

	public array
	$Default = [ ];

	public array
	$Primary = [ 'Bold'=> TRUE, 'Colour'=> '#F6684E' ];

	public array
	$Accent = [ 'Bold'=> TRUE, 'Colour'=> '#E3C099' ];

	public array
	$Error = [ 'Bold'=> TRUE, 'Colour'=> '#E17B7B' ];

	public array
	$OK = [ 'Bold'=> TRUE, 'Colour'=> '#4EA125' ];

	public array
	$Strong = [ 'Bold'=> TRUE ];

	public array
	$Muted = [ 'Colour'=> '#666666' ];

	////////

	public string
	$CharBullet = '•';

	public string
	$CharHeader = '█';

	////////////////////////////////////////////////////////////////
	////////////////////////////////////////////////////////////////

	public function
	__Construct() {

		return;
	}

	public function
	Has(string $Key):
	bool {

		return isset($this->{$Key});
	}

	public function
	Get(string $Key):
	mixed {

		return $this->{$Key};
	}

};

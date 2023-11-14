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
	Warning = 'Warning',
	Alert   = 'Alert',
	Strong  = 'Strong',
	Muted   = 'Muted';

	const
	Types = [
		self::Default,
		self::Prime, self::Accent,
		self::OK, self::Error, self::Alert, self::Warning,
		self::Strong, self::Muted
	];

	////////////////////////////////////////////////////////////////
	////////////////////////////////////////////////////////////////

	public array
	$Default;

	public array
	$Primary;

	public array
	$Accent;

	public array
	$OK;

	public array
	$Error;

	public array
	$Warning;

	public array
	$Alert;

	public array
	$Strong;

	public array
	$Muted;

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

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
	$Default = [ ];

	public array
	$Strong = [ 'Bold'=> TRUE ];

	public array
	$Primary = [ ];

	public array
	$Accent = [ ];

	public array
	$OK = [ ];

	public array
	$Error = [ ];

	public array
	$Warning = [ ];

	public array
	$Alert = [ ];

	public array
	$Muted = [ ];

	////////

	#[Common\Meta\Date('2023-11-15')]
	#[Common\Meta\Info('Thicc filled dot.')]
	public string|int
	$CharBullet = 0x2022; // thicc dot

	#[Common\Meta\Date('2023-11-15')]
	#[Common\Meta\Info('Empty circle that should pair with CharBullet.')]
	public string|int
	$CharCircleFilled = 0x25CB;

	#[Common\Meta\Date('2023-11-15')]
	#[Common\Meta\Info('Empty circle that should pair with CharBullet.')]
	public string|int
	$CharCircleEmpty = 0x25CB;

	#[Common\Meta\Date('2023-11-15')]
	#[Common\Meta\Info('Solid AF.')]
	public string|int
	$CharBlock = 0x2588;

	////////////////////////////////////////////////////////////////
	////////////////////////////////////////////////////////////////

	public function
	__Construct() {

		return;
	}

	////////////////////////////////////////////////////////////////
	////////////////////////////////////////////////////////////////

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

	////////////////////////////////////////////////////////////////
	////////////////////////////////////////////////////////////////

	public function
	GetCharBullet():
	string {

		if(is_int($this->CharBullet))
		return mb_chr($this->CharBullet);

		return $this->CharBullet;
	}

	public function
	GetCharCircleEmpty():
	string {

		if(is_int($this->CharCircleEmpty))
		return mb_chr($this->CharCircleEmpty);

		return $this->CharCircleEmpty;
	}

	public function
	GetCharCircleFilled():
	string {

		if(is_int($this->CharCircleFilled))
		return mb_chr($this->CharCircleFilled);

		return $this->CharCircleFilled;
	}

	public function
	GetCharBlock():
	string {

		if(is_int($this->CharBlock))
		return mb_chr($this->CharBlock);

		return $this->CharBlock;
	}

};

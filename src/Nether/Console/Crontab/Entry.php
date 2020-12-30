<?php

namespace Nether\Console\Crontab;

use Nether;

use Exception;
use Stringable;

class Entry
implements Stringable {

	public String
	$Minute  = '*',
	$Hour    = '*',
	$Day     = '*',
	$Month   = '*',
	$Weekday = '*',
	$Command = '';

	static public Array
	$TimeMacros = [
		'@hourly'  => [ '0', '*', '*', '*', '*' ],
		'@daily'   => [ '0', '0', '*', '*', '*' ],
		'@weekly'  => [ '0', '0', '*', '*', '0' ],
		'@monthly' => [ '0', '0', '1', '*', '*' ],
		'@yearly'  => [ '0', '0', '1', '1', '*' ]
	];

	////////////////////////////////////////////////////////////////
	////////////////////////////////////////////////////////////////

	public function
	__Construct(?String $Line=NULL) {
	/*//
	@date 2020-12-30
	//*/

		if($Line !== NULL)
		$this->Parse($Line);

		return;
	}

	public function
	__ToString():
	String {
	/*//
	@date 2020-12-30
	//*/

		return $this->GetAsLine();
	}

	////////////////////////////////////////////////////////////////
	////////////////////////////////////////////////////////////////

	public function
	Parse(String $Line):
	static {
	/*//
	@date 2020-12-30
	//*/

		if(!$this->Parse_CronBasic($Line))
		if(!$this->Parse_CronMacro($Line))
		throw new Exception('unable to parse the line');

		return $this;
	}

	protected function
	Parse_CronBasic(String $Line):
	Bool {
	/*//
	@date 2020-12-30
	//*/

		$Match = NULL;
		$Pattern = '/^([^#\h]+?) ([^\h]+?) ([^\h]+?) ([^\h]+?) ([^\h]+?) (.+)$/';

		if(!preg_match($Pattern,$Line,$Match))
		return FALSE;

		list(
			1 => $this->Minute,
			2 => $this->Hour,
			3 => $this->Day,
			4 => $this->Month,
			5 => $this->Weekday,
			6 => $this->Command
		) = $Match;

		return TRUE;
	}

	protected function
	Parse_CronMacro(String $Line):
	Bool {
	/*//
	@date 2020-12-30
	//*/

		$Match = NULL;
		$Pattern = '/^([^#\h]+?) (.+)$/';

		if(!preg_match($Pattern,$Line,$Match))
		return FALSE;

		if(!array_key_exists($Match[1],static::$TimeMacros))
		return FALSE;

		$this->Command = $Match[2];

		list(
			0 => $this->Minute,
			1 => $this->Hour,
			2 => $this->Day,
			3 => $this->Month,
			4 => $this->Weekday
		) = static::$TimeMacros[$Match[1]];

		return TRUE;
	}

	////////////////////////////////////////////////////////////////
	////////////////////////////////////////////////////////////////

	public function
	GetAsLine():
	String {
	/*//
	@date 2020-12-30
	//*/

		return sprintf(
			'%s %s %s %s %s %s',
			$this->Minute,
			$this->Hour,
			$this->Day,
			$this->Month,
			$this->Weekday,
			$this->Command
		);
	}

	////////////////////////////////////////////////////////////////
	////////////////////////////////////////////////////////////////

}

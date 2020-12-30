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

	public ?String
	$Comment = NULL;

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
		if(!$this->Parse_CronFancy($Line))
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
		$Pattern = '/^([^#]+?) (.+?) (.+?) (.+?) (.+?) (.+)$/';

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
	Parse_CronFancy(String $Line):
	Bool {
	/*//
	@date 2020-12-30
	//*/

		return FALSE;
	}

	protected function
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

	////////////////////////////////////////////////////////////////
	////////////////////////////////////////////////////////////////

}

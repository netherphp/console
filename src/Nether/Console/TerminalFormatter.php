<?php

namespace Nether\Console;

use Exception;

class TerminalFormatter {

	const Codes = [
		'Reset'     => 0,
		'Bright'    => 1, 'Bold'      => 1,
		'Dim'       => 2,
		'Underline' => 4,
		'Blink'     => 5,
		'Invert'    => 7,
		'Hidden'    => 8,

		'Black'   => 30, 'Black2'   => 90,
		'Red'     => 31, 'Red2'     => 91,
		'Green'   => 32, 'Green2'   => 92,
		'Yellow'  => 33, 'Yellow2'  => 93,
		'Blue'    => 34, 'Blue2'    => 94,
		'Magenta' => 35, 'Magenta2' => 95,
		'Cyan'    => 36, 'Cyan2'    => 96,
		'Grey'    => 37, 'Grey2'    => 97,
		'White'   => 97, 'White2'   => 97
	];

	public function
	__Invoke(...$Codes):
	String {
	/*//
	@date 2021-01-14
	//*/

		return $this->Sequence(...$Codes);
	}

	public function
	__Call(String $Fn, Array $Argv):
	Mixed {
	/*//
	@date 2021-01-14
	//*/

		$Output = NULL;
		$Codes = NULL;

		// digest this function name into codes.
		// eg. BoldYellow = [Bold, Yellow]

		preg_match_all('/[A-Z][a-z0-9]+/',$Fn,$Codes);

		if(!array_key_exists(0,$Codes))
		throw new Exception('error handing __call fn name');

		if(!count($Codes[0]))
		throw new Exception('error handing __call fn name');

		// generate the sequence.

		$Output = $this->Sequence(...$Codes[0]);

		if(count($Argv) >= 1 && is_string($Argv[0])) {
			$Output .= $Argv[0];
			$Output .= $this->Sequence('Reset');
		}

		return $Output;
	}

	public function
	__Get(String $Fn):
	Mixed {
	/*//
	@date 2021-01-25
	//*/

		return $this->__Call($Fn,[]);
	}

	public function
	Sequence(...$Codes):
	String {
	/*//
	@date 2021-01-14
	//*/

		$Output = '';
		$Code = NULL;

		if(!count($Codes))
		$Codes[] = 'Reset';

		foreach($Codes as &$Code)
		if(array_key_exists($Code,static::Codes))
		$Code = static::Codes[$Code];

		return sprintf("\e[%sm",join(';',$Codes));
	}

}

<?php

namespace Nether\Console;

use Exception;

class TerminalFormatter {

	const
	Codes = [
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
		'White'   => 97, 'White2'   => 97,

		'_Red'     => 41,
		'_Green'   => 42,
		'_Yellow'  => 43,
		'_Blue'    => 44,
		'_Magenta' => 45,
		'_Cyan'    => 46,
		'_White'   => 47
	];

	private
	Bool $Enabled = TRUE;

	////////
	////////

	public function
	__Construct() {
	/*//
	@date 2021-01-26
	//*/

		// most use cases are in terminal apps being used so we will
		// use this as a means to enable or disable the formatter by
		// default. if it looks like we are in a terminal we will
		// enable the escape sequences.

		// this will make it so piping or redirecting output will not
		// send the colour codes to the files.

		$this->Enabled = stream_isatty(STDOUT);

		return;
	}

	public function
	__Invoke(...$Codes):
	string {
	/*//
	@date 2021-01-14
	//*/

		return $this->Sequence(...$Codes);
	}

	public function
	__Call(string $Fn, array $Argv):
	mixed {
	/*//
	@date 2021-01-14
	//*/

		$Output = NULL;
		$Codes = NULL;

		// digest this function name into codes.
		// eg. BoldYellow = [Bold, Yellow]

		preg_match_all('/_?[A-Z][a-z0-9]+/',$Fn,$Codes);

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
	__Get(string $Fn):
	mixed {
	/*//
	@date 2021-01-25
	//*/

		return $this->__Call($Fn,[]);
	}

	public function
	__Set(string $Fn, mixed $Vz):
	void {
	/*//
	@date 2021-01-26
	//*/

		return;
	}

	////////
	////////

	public function
	Enable(bool $Enabled=TRUE):
	static {
	/*//
	@date 2021-01-26
	//*/

		$this->Enabled = $Enabled;
		return $this;
	}

	public function
	Disable():
	static {
	/*//
	@date 2021-01-26
	//*/

		return $this->Enable(FALSE);
	}

	public function
	IsEnabled():
	bool {
	/*//
	@date 2021-01-26
	//*/

		return $this->Enabled;
	}

	////////
	////////

	public function
	Sequence(...$Codes):
	string {
	/*//
	@date 2021-01-14
	//*/

		$Code = NULL;
		$Final = [];

		if(!$this->Enabled)
		return '';

		////////

		if(!count($Codes))
		$Codes[] = 'Reset';

		foreach($Codes as $Code)
		if(array_key_exists($Code,static::Codes))
		$Final[] = static::Codes[$Code];

		if(!count($Final))
		return '';

		return sprintf("\e[%sm",join(';',$Final));
	}

}

<?php

namespace Nether\Console;

use Nether\Console\TerminalFormatter;
use Nether\Console\Struct\CommandArgs;

class Util {

	static public function
	PascalToKey(?string $Input):
	string {

		if($Input === NULL)
		return '';

		$Output = preg_replace(
			'/([a-z])([A-Z0-9])/',
			'$1-$2',
			$Input
		);

		return strtolower($Output);
	}


	static public function
	ParseCommandArgs(array $Argv):
	CommandArgs {

		$Output = new CommandArgs;
		$Option = NULL;
		$Segment = NULL;

		$Output->Source = $Argv;

		foreach($Argv as $Segment) {
			if($Option = static::ParseCommandOption($Segment))
			$Output->Options->MergeRight($Option);

			else
			$Output->Inputs->Push($Segment);
		}

		return $Output;
	}

	static public function
	ParseCommandOption(string $Input):
	?array {

		$Match = NULL;

		if(preg_match('/^(-{1,2})/', $Input, $Match)) {
			if($Match[1] === '--')
			return static::ParseCommandOption_LongForm($Input);

			elseif($Match[1] === '-')
			return static::ParseCommandOption_ShortForm($Input);
		}

		return NULL;
	}

	static protected function
	ParseCommandOption_LongForm(string $Input):
	?array {

		$Output = [];

		$Opt = explode('=', $Input, 2);
		$Opt[0] = strtolower(ltrim($Opt[0], '-'));

		if(!$Opt[0])
		return NULL;

		switch(count($Opt)) {
			case 1: {
				$Output[$Opt[0]] = TRUE;
				break;
			}
			case 2: {
				$Output[$Opt[0]] = trim($Opt[1]);
				break;
			}
		}

		return $Output;
	}

	static protected function
	ParseCommandOption_ShortForm(string $Input):
	?array {

		$Output = [];
		$Letter = NULL;
		$Value = FALSE;
		$Opt = explode('=', ltrim($Input,'-'), 2);

		// figure out what the last value was.

		if(count($Opt) === 2)
		$Value = trim($Opt[1]);

		else
		$Value = TRUE;

		// break the options apart setting them true.

		if($Opt[0])
		foreach(str_split($Opt[0]) as $Letter)
		$Output[$Letter] = TRUE;

		// if the parsing did not really work send null out.

		if(!count($Output))
		return NULL;

		// then write the optional value to the last argument.

		end($Output);
		$Output[key($Output)] = $Value;
		reset($Output);

		return $Output;
	}


	static public function
	VarDump(mixed $Input, bool $Colour=TRUE):
	void {
	/*//
	@date 2022-08-11
	@todo finish this i did it wrong while xdebug was fucking about with it.
	//*/

		$F = new TerminalFormatter;
		$F->Enable($Colour);

		ob_start();
		var_dump($Input);
		$Output = ob_get_clean();

		// fixes the annoying newline after the arrow.

		$Output = preg_replace(
			'/(\$.+?) =>[\h\s\n]+/',
			'\\1 => ',
			$Output
		);

		$Output = preg_replace(
			'/^([\h]+)(.+?) ?(\$.+?) =>[\h\s\n]+/ms',
			sprintf(
				'\\1 %s %s = ',
				$F->Yellow('\\2'),
				$F->YellowBold('\\3')
			),
			$Output
		);

		// convert indention to tabs.

		$Output = preg_replace_callback(
			'#^(\h+)#ms',
			(
				fn(array $Result)
				=> str_repeat("\t", floor(strlen($Result[1]) / 2.0))
			),
			$Output
		);

		echo $Output;
		return;
	}

	static public function
	ObjectDump(object|array|NULL $Input, bool $Colour=TRUE):
	void {

		$Key = NULL;
		$Val = NULL;
		$F = new TerminalFormatter($Colour);

		if($Input === NULL) {
			echo 'NULL', PHP_EOL;
			return;
		}

		if(is_object($Input))
		printf(
			'%s %s%s',
			$F->YellowBold('Object:'),
			$F->Yellow($Input::class),
			PHP_EOL
		);

		elseif(is_array($Input))
		printf(
			'%s%s',
			$F->YellowBold('Array:'),
			PHP_EOL
		);

		foreach($Input as $Key => $Val) {
			if(is_object($Input))
			printf(
				'%s%s = %s%s',
				$F->MagentaBold('->'), $F->Magenta($Key), $Val,
				PHP_EOL
			);

			else
			printf(
				'%s%s%s = %s%s',
				$F->CyanBold('['),
				$F->Cyan($Key),
				$F->CyanBold(']'),
				$Val,
				PHP_EOL
			);
		}

		return;
	}

}

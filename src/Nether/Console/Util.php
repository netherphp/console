<?php

namespace Nether\Console;

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

}

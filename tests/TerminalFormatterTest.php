<?php

namespace Nether\Avenue;

use Closure;
use Nether;
use PHPUnit;

use Stringable;
use Nether\Console\TerminalFormatter;

class FormatterErrorCheck
implements Stringable {

	public String $Expect;
	public String $Message;
	public String $Result;

	public function
	__Construct(String $Expect, String $Message) {
		$this->Expect = $Expect;
		$this->Message = $Message;
		return;
	}

	public function
	__ToString():
	String { return $this->Message; }

	public function
	__Invoke(String $Result):
	Bool { return (($this->Result = $Result) === $this->Expect); }

};

class TerminalFormatterTest
extends PHPUnit\Framework\TestCase {
/*//
@date 2021-01-23
//*/

	/** @test */
	public function
	TestCheckFormatterErrorCheck() {
	/*//
	@date 2021-01-23
	//*/

		$Bob = new FormatterErrorCheck(
			'Bob',
			'could not even say my name'
		);

		$this->AssertEquals(
			('Bob'==='Bob'),
			($Bob('Bob')),
			((String)$Bob)
		);

		return;
	}

	/** @test */
	public function
	TestBasicFormatterSequences() {
	/*//
	@date 2021-01-23
	//*/

		$F = new TerminalFormatter;
		$Method = NULL;
		$Test = NULL;

		$TestSingle = [
			'Red' => new FormatterErrorCheck(
				"\e[31m",
				'expected \e[31m'
			),
			'White_Red' => new FormatterErrorCheck(
				"\e[97;41m",
				'expected \e[97;41m'
			),
			'BrightWhiteUnderline_Red' => new FormatterErrorCheck(
				"\e[1;97;4;41m",
				'expected \e[1;97;4;41m'
			)
		];

		$TestWrapped = [
			'Yellow' => new FormatterErrorCheck(
				"\e[33mYellow\e[0m",
				'expected \e[31m'
			),
			'Black_Yellow' => new FormatterErrorCheck(
				"\e[30;43mBlack_Yellow\e[0m",
				'expected \e[30;43mBlack_Yellow\e[0m'
			),
			'DimBlackUnderline_Yellow' => new FormatterErrorCheck(
				"\e[2;30;4;43mDimBlackUnderline_Yellow\e[0m",
				'expected \e[2;30;4;43mDimBlackUnderline_Yellow\e[0m'
			)
		];

		////////

		// using assert true beacuse assert equals will dump
		// a diff of the values and break your terminal lol.

		foreach($TestSingle as $Method => $Test)
		$this->AssertTrue(
			$Test($F->{$Method}()),
			sprintf(
				"Method({$Method}, {$Test}) Result(%s)",
				filter_var(
					$Test->Result,
					FILTER_SANITIZE_ENCODED,
					['flags'=>FILTER_FLAG_ENCODE_LOW]
				)
			)
		);

		foreach($TestWrapped as $Method => $Test)
		$this->AssertTrue(
			$Test($F->{$Method}($Method)),
			sprintf(
				"Method({$Method}, {$Test}) Result(%s)",
				filter_var(
					$Test->Result,
					FILTER_SANITIZE_ENCODED,
					['flags'=>FILTER_FLAG_ENCODE_LOW]
				)
			)
		);

		return;
	}

};

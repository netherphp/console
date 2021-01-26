<?php

namespace Nether\Avenue;

use Closure;
use Nether;
use PHPUnit;

use Stringable;
use Nether\Console\TerminalFormatter;

class FormatterErrorCheck
implements Stringable {
/*//
@date 2021-01-23
//*/

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

	static public function
	NewFormatter():
	TerminalFormatter {
	/*//
	@date 2021-01-26
	//*/

		return (
			(new TerminalFormatter)
			->Enable(TRUE)
		);
	}

	static public function
	Escapify(String $Input):
	String {
	/*//
	@date 2021-01-26
	//*/

		return filter_var(
			$Input,
			FILTER_SANITIZE_ENCODED,
			['flags'=>FILTER_FLAG_ENCODE_LOW]
		);
	}

	/** @test */
	public function
	TestCheckFormatterErrorCheck():
	Void {
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
	TestMagicInvokeSingleSequence() {
	/*//
	@date 2021-01-23
	//*/

		$F = static::NewFormatter();
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

		////////

		// using assert true beacuse assert equals will dump
		// a diff of the values and break your terminal lol.

		foreach($TestSingle as $Method => $Test)
		$this->AssertTrue(
			$Test($F->{$Method}()),
			sprintf(
				'Method(%s, %s) Result(%s)',
				$Method,
				$Test,
				static::Escapify($Test->Result)
			)
		);

		return;
	}

	/** @test */
	public function
	TestMagicInvokeWrappedSequence() {
	/*//
	@date 2021-01-23
	//*/

		$F = static::NewFormatter();
		$Method = NULL;
		$Test = NULL;

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

		foreach($TestWrapped as $Method => $Test)
		$this->AssertTrue(
			$Test($F->{$Method}($Method)),
			sprintf(
				"Method({$Method}, {$Test}) Result(%s)",
				static::Escapify($Test->Result)
			)
		);

		return;
	}

	/** @test */
	public function
	TestMagicGetSingleSequence() {
	/*//
	@date 2021-01-23
	//*/

		$F = static::NewFormatter();
		$Prop = NULL;
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

		////////

		// using assert true beacuse assert equals will dump
		// a diff of the values and break your terminal lol.

		foreach($TestSingle as $Prop => $Test)
		$this->AssertTrue(
			$Test($F->{$Prop}),
			sprintf(
				'Property(%s, %s) Result(%s)',
				$Prop,
				$Test,
				static::Escapify($Test->Result)
			)
		);

		return;
	}

	/** @test */
	public function
	TestSequenceOrderAgnosticfulness() {
	/*//
	@date 2021-01-26
	//*/

		$F = static::NewFormatter();
		$Prop = NULL;
		$Test = NULL;

		$TestSingle = [
			'BrightWhiteUnderline_Red' => new FormatterErrorCheck(
				"\e[1;97;4;41m",
				'expected \e[1;97;4;41m'
			),
			'BrightWhite_RedUnderline' => new FormatterErrorCheck(
				"\e[1;97;41;4m",
				'expected \e[1;97;41;4m'
			),
			'Bright_RedWhiteUnderline' => new FormatterErrorCheck(
				"\e[1;41;97;4m",
				'expected \e[1;41;97;4m'
			),
			'_RedBrightWhiteUnderline' => new FormatterErrorCheck(
				"\e[41;1;97;4m",
				'expected \e[41;1;97;4m'
			)
		];

		////////

		// using assert true beacuse assert equals will dump
		// a diff of the values and break your terminal lol.

		foreach($TestSingle as $Prop => $Test)
		$this->AssertTrue(
			$Test($F->{$Prop}),
			sprintf(
				'Property(%s, %s) Result(%s)',
				$Prop,
				$Test,
				static::Escapify($Test->Result)
			)
		);

		return;
	}

	/** @test */
	public function
	TestMagicReaderConsistency():
	Void {
	/*//
	@date 2021-01-26
	//*/

		$F = static::NewFormatter();

		$this->AssertTrue(
			(
				($F->Sequence('Bright','Cyan') === $F->BrightCyan)
				&& ($F->Sequence('Bright','Cyan') === $F->BrightCyan())
				&& ($F->BrightCyan === $F->BrightCyan())
			),
			'the triangle was not complete'
		);

		return;
	}

	/** @test */
	public function
	TestSequencesWithInvalidBits():
	Void {
	/*//
	@date 2021-01-26
	//*/

		$F = static::NewFormatter();
		$Want = "\e[31m";

		$this->AssertTrue(
			($F->Red === $Want),
			'basic valid sequence'
		);

		$this->AssertTrue(
			($F->Rip === ''),
			'basic invalid sequence'
		);

		$this->AssertTrue(
			($F->RedRip === $Want),
			'partial invalid sequence'
		);

		$this->AssertTrue(
			($F->RipRed === $Want),
			'partial invalid sequence'
		);

		return;
	}

	/** @test */
	public function
	TestEnablerToggle() {
	/*//
	@date 2021-01-26
	//*/

		$F = static::NewFormatter();

		$F->Enable(TRUE);
		$this->AssertTrue($F->IsEnabled());

		$F->Enable(FALSE);
		$this->AssertFalse($F->IsEnabled());

		$F->Enable();
		$this->AssertTrue(
			($F->Red() !== ''),
			'formatter did not get enabled'
		);

		$F->Disable();
		$this->AssertTrue(
			($F->Red() === ''),
			'formatter did not get disabled'
		);

		return;
	}

};

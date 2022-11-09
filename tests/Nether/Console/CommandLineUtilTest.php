<?php

namespace Nether\Console;
use Nether;
use PHPUnit;

class CommandLineUtilTest
extends PHPUnit\Framework\TestCase {

	/** @test */
	public function
	TestBasic():
	void {

		$CLI = NULL;
		$Output = NULL;

		////////

		// test it looks clean before execution.

		$CLI = new Nether\Console\Struct\CommandLineUtil('echo lol');
		$this->AssertIsArray($CLI->Output);
		$this->AssertCount(0, $CLI->Output);
		$this->AssertIsInt($CLI->Error);
		$this->AssertEquals(0, $CLI->Error);

		// test it looks clean after execution.

		$CLI->Run();
		$this->AssertIsArray($CLI->Output);
		$this->AssertCount(1, $CLI->Output);
		$this->AssertIsInt($CLI->Error);
		$this->AssertEquals(0, $CLI->Error);

		// test that printing the output looks right.

		ob_start();
		$CLI->Print();
		$Output = trim(ob_get_clean());

		$this->AssertEquals('lol', $Output);

		return;
	}

	/** @test */
	public function
	TestBasicFailure():
	void {


		$CLI = NULL;
		$Output = NULL;

		////////

		// test a sucess.

		$Bin = Nether\Console\Util::Repath('bin/nethercon');

		$CLI = new Nether\Console\Struct\CommandLineUtil("php {$Bin} failboat --defy");
		$this->AssertEquals(0, $CLI->Error);

		$CLI->Run();
		$this->AssertEquals(0, $CLI->Error);

		// test a fail.

		unset($CLI);
		$CLI = new Nether\Console\Struct\CommandLineUtil("php {$Bin} failboat --hard");
		$this->AssertEquals(0, $CLI->Error);

		$CLI->Run();
		$this->AssertEquals(2, $CLI->Error);

		return;
	}

}

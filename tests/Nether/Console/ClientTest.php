<?php

namespace NetherTestSuite\Console\Client;
use Nether;
use PHPUnit;

use Exception;

////////////////////////////////////////////////////////////////
////////////////////////////////////////////////////////////////

class TestApp
extends Nether\Console\Client {

	#[Nether\Console\Meta\Command]
	public function
	Test():
	int {

		return 0;
	}

};

////////////////////////////////////////////////////////////////
////////////////////////////////////////////////////////////////

class ClientTest
extends PHPUnit\Framework\TestCase {

	/** @test */
	public function
	TestBasic():
	void {

		$App = new TestApp;

		$this->AssertEquals(2, $App->Commands->Count());
		$this->AssertTrue($App->Commands->HasKey('test'));
		$this->AssertTrue($App->Commands->HasKey('help'));

		return;
	}

	/**
	 * @test
	 * @runInSeparateProcess
	 */
	public function
	TestServerArgv():
	void {

		$HadFail = FALSE;
		$_SERVER['argv'] = [ 'test.lulz', 'lulz' ];

		// it should be impossible to fail with the forced data.

		$App = new TestApp;
		$this->AssertEquals('test.lulz', $App->Name);
		$this->AssertEquals('lulz', $App->Command);

		// it should be impossible to succeed with the deleted data.

		try {
			$HadFail = FALSE;

			unset($_SERVER['argv']);
			$App = new TestApp;
		}

		catch(Exception $Err) {
			$HadFail = TRUE;
			$this->AssertInstanceOf(
				Nether\Console\Error\RegisterArgcArgvUndefined::class,
				$Err
			);
		}

		$this->AssertTrue($HadFail);

		return;
	}

	/** @test */
	public function
	TestGetInput():
	void {

		$App = new TestApp([
			'test.lulz',
			'cmdlulz',
			'lulz1',
			'--val1=1',
			'lulz2',
			'--val2=2',
			'-rofl'
		]);

		$this->AssertEquals('test.lulz', $App->Name);
		$this->AssertEquals('cmdlulz', $App->Command);
		$this->AssertEquals('lulz1', $App->GetInput(1));
		$this->AssertEquals('lulz2', $App->GetInput(2));
		$this->AssertEquals('1', $App->GetOption('val1'));
		$this->AssertEquals('2', $App->GetOption('val2'));
		$this->AssertEquals(TRUE, $App->GetOption('r'));
		$this->AssertEquals(TRUE, $App->GetOption('o'));
		$this->AssertEquals(TRUE, $App->GetOption('f'));
		$this->AssertEquals(TRUE, $App->GetOption('l'));

		return;
	}

};

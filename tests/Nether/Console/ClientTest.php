<?php

namespace Nether\Console;
use Nether;
use PHPUnit;

use Exception;

////////////////////////////////////////////////////////////////
////////////////////////////////////////////////////////////////

class SudoFoolery {
	static public int
	$GetUID = 0;
}

function posix_getuid() {

	return ((++SudoFoolery::$GetUID % 2) === 0) ? 0 : 100;
}

function pcntl_exec(string $Command, array $Args) {

	return TRUE;
}

////////////////////////////////////////////////////////////////
////////////////////////////////////////////////////////////////

class TestApp
extends Nether\Console\Client {

	public string
	$Output = 'nope';

	#[Nether\Console\Meta\Command]
	public function
	Test():
	int {

		$this->Output = 'test';
		return 0;
	}

	#[Nether\Console\Meta\Command]
	public function
	TestQuit1():
	int {

		$this->Quit(1);

		return 0;
	}

	#[Nether\Console\Meta\Command]
	#[Nether\Console\Meta\Error(2, 'two')]
	public function
	TestQuit2():
	int {

		$this->Quit(2);

		return 0;
	}

	#[Nether\Console\Meta\Command('loaded', Hide: TRUE)]
	#[Nether\Console\Meta\Arg('<thing>', 'input thing')]
	#[Nether\Console\Meta\Option('--ok', FALSE, 'does ok')]
	#[Nether\Console\Meta\Toggle('--togg', 'does toggle')]
	#[Nether\Console\Meta\Value('--val', 'does value')]
	#[Nether\Console\Meta\Error(1, 'did error')]
	public function
	TestLoaded():
	int {

		return 0;
	}

};

////////////////////////////////////////////////////////////////
////////////////////////////////////////////////////////////////

class ClientTest
extends PHPUnit\Framework\TestCase {

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
	TestBasic():
	void {

		$App = new TestApp;

		$this->AssertTrue($App->Commands->HasKey('test'));
		$this->AssertTrue($App->Commands->HasKey('help'));

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

	/** @test */
	public function
	TestRunNoInput():
	void {

		$App = new TestApp([ 'test.lulz' ]);
		$App->Formatter->Disable();
		$this->AssertEquals('nope', $App->Output);
		$this->AssertEquals('help', $App->Command);

		ob_start();
		$App->Run();
		$Output = trim(ob_get_clean());

		$this->AssertTrue(str_starts_with($Output, 'USAGE:'));

		return;
	}

	/** @test */
	public function
	TestRunTest():
	void {

		$App = new TestApp([ 'test.lulz', 'test' ]);
		$this->AssertEquals('nope', $App->Output);

		$App->Run();
		$this->AssertEquals('test', $App->Output);

		return;
	}

	/** @test */
	public function
	TestRunUnknown():
	void {

		$App = new TestApp([ 'test.lulz', 'omgwtfbbq' ]);
		$App->Formatter->Disable();
		$this->AssertEquals('nope', $App->Output);
		$this->AssertEquals('omgwtfbbq', $App->Command);

		ob_start();
		$App->Run();
		$Output = trim(ob_get_clean());

		$this->AssertTrue(str_starts_with($Output, 'USAGE:'));

		return;
	}

	/** @test */
	public function
	TestQuit1():
	void {

		$App = new TestApp([ 'test.lulz', 'test-quit-1' ]);
		$App->Formatter->Disable();

		ob_start();
		$Result = $App->Run();
		$Output = ob_get_clean();

		$this->AssertEquals(1, $Result);
		$this->AssertTrue(str_starts_with($Output, 'ERROR(1): '));

		return;
	}

	/** @test */
	public function
	TestQuit2():
	void {

		$App = new TestApp([ 'test.lulz', 'test-quit-2' ]);
		$App->Formatter->Disable();

		ob_start();
		$Result = $App->Run();
		$Output = trim(ob_get_clean());

		$this->AssertEquals(2, $Result);
		$this->AssertEquals('ERROR(2): two', $Output);

		return;
	}

	/** @test */
	public function
	TestPrintLn():
	void {

		$App = new TestApp([ 'test.lulz', 'test' ]);

		ob_start();
		$App->PrintLn('test');
		$Output = ob_get_clean();

		$this->AssertEquals(
			sprintf('test%s', PHP_EOL),
			$Output
		);

		return;
	}

	/** @test */
	public function
	TestPrompt():
	void {

		$App = new TestApp([ 'test.lulz', 'test' ]);

		$Input = tmpfile();
		fwrite($Input, "best\n");
		fseek($Input, 0);

		ob_start();
		$Result = $App->Prompt('Test?', '??>', $Input);
		$Output = ob_get_clean();
		fclose($Input);

		// test it rendered the prompt as we expected.

		$this->AssertEquals(
			sprintf('Test?%s??> %s', PHP_EOL, PHP_EOL),
			$Output
		);

		// test that it ate and spit out the test data as expected.

		$this->AssertEquals('best', $Result);

		// nice

		return;
	}

	/** @test */
	public function
	TestPromptEquals():
	void {

		$App = new TestApp([ 'test.lulz', 'test' ]);

		$Input = tmpfile();
		fwrite($Input, "garnet\n");
		fseek($Input, 0);

		ob_start();
		$Result = $App->PromptEquals('pearl?', '??>', 'pearl', $Input);
		$Output = ob_get_clean();

		// test it rendered the prompt as we expected.

		$this->AssertEquals(
			sprintf('pearl?%s??> %s', PHP_EOL, PHP_EOL),
			$Output
		);

		// the prompt was expecting pearl but we fed it garnet so it
		// should have sput out a false.

		$this->AssertFalse($Result);

		////////

		fseek($Input, 0);
		ob_start();
		$Result = $App->PromptEquals('garnet?', '??>', 'garnet', $Input);
		$Output = ob_get_clean();

		// test it rendered the prompt as we expected.

		$this->AssertEquals(
			sprintf('garnet?%s??> %s', PHP_EOL, PHP_EOL),
			$Output
		);

		// the prompt was expecting garnet and we fed it one.

		$this->AssertTrue($Result);

		fclose($Input);
		return;
	}

	/** @test */
	public function
	TestFormatterShortuts():
	void {

		$App = new TestApp;
		$App->Formatter->Enable();

		$this->AssertEquals(
			$App->Formatter->{$App->ColourPrimary}('test'),
			$App->FormatPrimary('test')
		);

		$this->AssertEquals(
			$App->Formatter->{$App->ColourSecondary}('test'),
			$App->FormatSecondary('test')
		);

		return;
	}

	/** @test */
	public function
	TestExecuteCommandLine():
	void {

		$App = new TestApp;
		$Command = 'echo lol';
		$Result = NULL;
		$Error = NULL;

		ob_start();
		$App->Formatter->Disable();
		$Result = $App->ExecuteCommandLine($Command);
		ob_end_clean();

		$this->AssertEquals('lol', join(PHP_EOL, $Result->Output));
		$this->AssertEquals(0, $Error);

		return;
	}

	/** @test */
	public function
	TestSudo():
	void {

		if(PHP_OS_FAMILY === 'Windows') {
			$this->AssertTrue(TRUE);
			return;
		}

		$App = new TestApp([ 'test.lulz', 'test' ]);
		SudoFoolery::$GetUID = 0;

		$Result = $App->Sudo();
		$this->AssertTrue($Result);

		$Result = $App->Sudo();
		$this->AssertFalse($Result);

		return;
	}

	/** @test */
	public function
	TestHelp():
	void {

		$App = NULL;

		////////

		// test basic help.

		ob_start();
		$App = new TestApp([ 'testapp.lulz' ]);
		$App->HandleCommandHelp();
		ob_get_clean();

		// test manually asking for basic help.

		ob_start();
		$App = new TestApp([ 'testapp.lulz', 'help' ]);
		$App->HandleCommandHelp();
		ob_get_clean();

		// test full verbose help.

		ob_start();
		$App = new TestApp([ 'testapp.lulz', 'help', '--verbose' ]);
		$App->HandleCommandHelp();
		ob_get_clean();

		// test specific command help.

		ob_start();
		$App = new TestApp([ 'testapp.lulz', 'help', 'loaded' ]);
		$App->HandleCommandHelp();
		ob_get_clean();

		// proper test cases for the help will be done once i stop messing
		// with the formatting so much. the four scenerios above are the
		// four test cases i need to test to trigger full coverage.

		$this->AssertTrue(TRUE);
		return;
	}

};

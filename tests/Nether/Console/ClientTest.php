<?php

namespace Nether\Avenue;

use Nether;
use PHPUnit;

////////////////////////////////
////////////////////////////////

class LocalExtensionTest
extends Nether\Console\Client {
	public function
	HandleTaco() { return 42; }
	public function
	HandleNacho() { return func_num_args() === 0; }
}

////////////////////////////////
////////////////////////////////

class ClientTest
extends PHPUnit\Framework\TestCase {

	static public function
	LocalNachoFunction($cli) {
	/*//
	dummy function for TestFunctionPassesObject.
	//*/

		return (func_num_args()===1 && $cli instanceof Nether\Console\Client);
	}

	////////
	////////

	public function
	SetUp():
	Void {
	/*//
	phpunit test env setup.
	//*/

		$_SERVER['argv'] = ['test.php','taco','omg','--lol=bbq'];
		return;
	}

	/** @test */
	public function
	TestGetMethodFromCommand() {
	/*//
	test that the command to methodname translation is working as intended
	for various inputs.
	//*/

		$this->AssertEquals(
			'HandleRun',
			Nether\Console\Client::GetMethodFromCommand('run')
		);

		////////
		////////

		foreach(['run-things','run_things','Run-ThiNgs'] as $cmd) $this
		->AssertEquals(
			'HandleRunThings',
			Nether\Console\Client::GetMethodFromCommand($cmd)
		);

		unset($cmd);

		return;
	}


	/** @test */
	public function
	TestParseCommandOption() {
	/*//
	test that input data as expected the way they come from _SERVER['argv']
	parses as valid option switches.
	//*/

		$this->AssertNull(
			Nether\Console\Client::ParseCommandOption('onlytest')
		);

		///////
		///////

		$option = Nether\Console\Client::ParseCommandOption('--onlytest');
		$this->AssertTrue(
			(array_key_exists('onlytest',$option) && $option['onlytest'] === true),
			'test long option with boolean value'
		);

		$option = Nether\Console\Client::ParseCommandOption('--onlytest=true');
		$this->AssertTrue(
			(array_key_exists('onlytest',$option) && $option['onlytest'] === 'true'),
			'test long option with string value'
		);

		$option = Nether\Console\Client::ParseCommandOption('--onlytest=true for sure');
		$this->AssertTrue(
			(array_key_exists('onlytest',$option) && $option['onlytest'] === 'true for sure'),
			'test long option with spaced string value'
		);

		///////
		///////

		$option = Nether\Console\Client::ParseCommandOption('-zomg');
		$this->AssertTrue(
			(is_array($option) && count($option) === 4),
			'test single character options parsing'
		);
		foreach(['z','o','m','g'] as $l) $this->AssertTrue(
			(array_key_exists($l,$option) && $option[$l] === true),
			'test single character option values'
		);
		unset($l);

		///////
		///////

		$option = Nether\Console\Client::ParseCommandOption('-zomg=bbq');
		$this->AssertTrue(
			(is_array($option) && count($option) === 4),
			'test single character options parsing with final string value'
		);
		foreach(['z'=>true,'o'=>true,'m'=>true,'g'=>'bbq'] as $l => $v)
		$this->AssertTrue(
			(array_key_exists($l,$option) && $option[$l] === $v),
			'test single character option values with final string value'
		);
		unset($l,$v);

		///////
		///////

		return;
	}

	/** @test */
	public function
	TestParseCommandArgs() {
	/*//
	test that passing command arrays in the same format as they come from
	_SERVER['argv'] parse as expected.
	//*/

		$data = Nether\Console\Client::ParseCommandArgs([
			'one', 'two', '--three', '--four=true',
			'five', '--six=end of test', '-zomg', '-lmao=ayy'
		]);

		// note, -m and -o appear twice in this dataset.

		$this->AssertTrue(
			(count($data['Inputs']) === 3),
			'test parsed the right number of inputs'
		);

		$this->AssertTrue($data['Inputs'][0] === 'one');
		$this->AssertTrue($data['Inputs'][1] === 'two');
		$this->AssertTrue($data['Inputs'][2] === 'five');

		$this->AssertTrue(
			(count($data['Options']) === 9),
			'test parsed right number of options'
		);

		$this->AssertTrue($data['Options']['three'] === true);
		$this->AssertTrue($data['Options']['four'] === 'true');
		$this->AssertTrue($data['Options']['six'] === 'end of test');
		$this->AssertTrue($data['Options']['z'] === true);
		$this->AssertTrue($data['Options']['g'] === true);
		$this->AssertTrue($data['Options']['l'] === true);
		$this->AssertTrue($data['Options']['m'] === true);
		$this->AssertTrue($data['Options']['a'] === true);
		$this->AssertTrue($data['Options']['o'] === 'ayy');

		return;
	}

	/** @test */
	public function
	TestGeneralUse() {
	/*//
	test that the basic mechanics of the object are working.
	//*/

		$cli = new Nether\Console\Client;
		$this->AssertTrue($cli->GetInput(1) === 'taco');
		$this->AssertTrue($cli->GetInput(2) === 'omg');
		$this->AssertTrue($cli->GetOption('lol') === 'bbq');

		return;
	}

	/** @test */
	public function
	TestInlineHandlerUse() {
	/*//
	test that the quick dirty way to use Console with inline handler
	definitions works as intended in general.
	//*/

		$cli = (new Nether\Console\Client)
		->SetHandler('taco',function(){ return 42; });

		$this->AssertTrue($cli->Run() === 42);
		return;
	}

	/** @test */
	public function
	TestExtendedHandlerUse() {
	/*//
	test that extending the class with handler methods works as intended
	in general.
	//*/

		$cli = new LocalExtensionTest;

		$this->AssertTrue($cli->Run() === 42);
		return;
	}

	/** @test */
	public function
	TestClosureBindsThis() {
	/*//
	test that when you define a handler with a closure, that the command
	object gets bound to $this instead of requiring an argument for the
	objet.
	//*/

		$that = $this;
		$cli = new Nether\Console\Client;

		$cli->SetHandler('taco',function() use($that){
			$that->AssertTrue(func_num_args() === 0);
			$that->AssertTrue($this instanceof Nether\Console\Client);
		});

		$cli->Run();

		return;
	}

	/** @test */
	public function
	TestExtensionLikeClosure() {
	/*//
	tests that the extended object works in the same way as the closure,
	not passing the cli argument in because its a fkn extension lol.
	//*/

		$_SERVER['argv'][1] = 'nacho';
		$cli = new LocalExtensionTest;

		$this->AssertTrue($cli->Run());
		return;
	}

	/** @test */
	public function
	TestFunctionPassesObject() {
	/*//
	tests that the client passes itself to boring functions that are set
	to be used as the handlers.
	//*/

		$_SERVER['argv'][1] = 'nacho';

		$cli = new Nether\Console\Client;
		$cli->SetHandler('nacho',[__CLASS__,'LocalNachoFunction']);

		$this->AssertTrue($cli->Run());
		return;
	}

	/** @test */
	public function
	TestOptionCaseSensitivity() {
	/*//
	that that short options are case sensitive while long options are not.
	//*/

		$_SERVER['argv'][1] = 'vehicle';
		$_SERVER['argv'][2] = '-t=car';
		$_SERVER['argv'][3] = '-T=muscle';
		$_SERVER['argv'][4] = '--make=Ford';
		$_SERVER['argv'][5] = '--Model=Mustang';
		$_SERVER['argv'][6] = '--yeaR=2016';

		$that = $this;

		$CLI = (new Nether\Console\Client)
		->SetHandler('vehicle',function() use($that){

			$that->AssertTrue(
				$this->GetOption('t') === 'car',
				'reading type flag (t) case sensitive'
			);

			$that->AssertTrue(
				$this->GetOption('T') === 'muscle',
				'reading template flag (T) case sensitive'
			);

			$that->AssertTrue(
				$this->GetOption('Make') === 'Ford',
				'reading make case insensitive'
			);

			$that->AssertTrue(
				$this->GetOption('Model') === 'Mustang',
				'reading model case insensitive'
			);

			$that->AssertTrue(
				$this->GetOption('Year') === '2016',
				'reading year case insensitive'
			);

			return TRUE;
		});

		$this->AssertTrue($CLI->Run());
		return;
	}

	/** @test */
	public function
	TestInputChaining() {
	/*//
	test that the input chaining option works as intended and that the proper
	value is returned as expected from a chain.
	//*/

		$CLI = (new Nether\Console\Client)
		->SetHandler('one',function(){ return 1; })
		->SetHandler('two',function(){ return 2; })
		->SetHandler('three',function(){ return 3; });

		// disallow chaining.
		$CLI->SetChainCommands(FALSE);
		$this->AssertTrue( $CLI->Run(['one','two','three']) === 1 );

		// allow chaining.
		$CLI->SetChainCommands(TRUE);
		$this->AssertTrue( $CLI->Run(['one','two','three']) === 3 );

		return;
	}

}
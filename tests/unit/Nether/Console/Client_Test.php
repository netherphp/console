<?php

namespace Nether\Avenue;
use \Nether;
use \Codeception;

////////////////////////////////
////////////////////////////////

class LocalExtensionTest extends Nether\Console\Client {
	public function HandleTaco() { return 42; }
}

////////////////////////////////
////////////////////////////////

class Console_Test extends \PHPUnit_Framework_TestCase {

	public function
	testGetMethodFromCommand() {

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


	public function
	testParseCommandOption() {
	/*//
	test that input data as expected the way they come from _SERVER['argv']
	parses as valid option switches.
	//*/

		$this->AssertFalse(
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

	public function
	testParseCommandArgs() {
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

	public function
	testGeneralUse() {
	/*//
	test that the basic mechanics of the object are working.
	//*/

		$_SERVER['argv'] = [
			'test.php', 'taco', 'omg', '--lol=bbq'
		];

		$cli = new Nether\Console\Client;
		$this->AssertTrue($cli->GetInput(1) === 'taco');
		$this->AssertTrue($cli->GetInput(2) === 'omg');
		$this->AssertTrue($cli->GetOption('lol') === 'bbq');

		return;
	}

	public function
	testInlineHandlerUse() {
	/*//
	test that the quick dirty way to use Console with inline handler
	definitions works as intended in general.
	//*/

		$_SERVER['argv'] = [
			'test.php', 'taco', 'omg', '--lol=bbq'
		];

		$cli = (new Nether\Console\Client)
		->SetHandler('taco',function(){ return 42; });

		$this->AssertTrue($cli->Run() === 42);
		return;
	}

	public function
	testExtendedHandlerUse() {
	/*//
	test that extending the class with handler methods works as intended
	in general.
	//*/

		$_SERVER['argv'] = [
			'test.php', 'taco', 'omg', '--lol=bbq'
		];

		$cli = new LocalExtensionTest;

		$this->AssertTrue($cli->Run() === 42);
		return;
	}

}
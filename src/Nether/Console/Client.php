<?php

namespace Nether\Console;
use \Nether;
use \Exception;

class Client {

	protected $CommandString;

	protected $Inputs = [];
	protected $Options = [];

	///////////////////////////////////////////////////////////////////////////
	///////////////////////////////////////////////////////////////////////////

	public function
	__construct($opt=null) {
		$this->ParseCommandLine();
		return;
	}

	///////////////////////////////////////////////////////////////////////////
	///////////////////////////////////////////////////////////////////////////

	protected function
	ParseCommandLine() {
	/*//
	parse the command line into digestable components.
	//*/

		if(!array_key_exists('argv',$_SERVER))
		throw new Exception('register_argc_argv must be enabled');

		$option = null;
		foreach($_SERVER['argv'] as $key => $segment) {
			if($key === 0) continue;

			if($option = $this->ParseCommandOption($segment)) {
				$this->Options[key($option)] = current($option);
			} else {
				$this->Inputs[] = $segment;
			}
		}

		return;
	}

	protected function
	ParseCommandOption($input) {
	/*//
	parse an option into digestable components. our command arguments are
	going to behave a little differently than most people are used to. for
	example, i am not going to allow "--option value" it must be
	"--option=value" - nor am i going to allow "-xzf" they must be separate
	so "-x -z -f" - for both ease of implementation and that i always thought
	blocks of them were dumb anyway.
	//*/

		if(!preg_match('/^-{1,2}/',$input)) return false;

		$opt = explode('=',$input,2);
		switch(count($opt)) {
			case 1: {
				$output[ltrim($opt[0],'-')] = true;
				break;
			}
			case 2: {
				$output[ltrim($opt[0],'-')] = trim($opt[1]);
				break;
			}
		}

		return $output;
	}

	///////////////////////////////////////////////////////////////////////////
	///////////////////////////////////////////////////////////////////////////

	public function GetInput($offset) {
	/*//
	fetch the specified input value by offset. if you want the 1st input then
	you will ask for 1. we will zero-pwn it internally.
	//*/

		--$offset;

		if(!array_key_exists($offset,$this->Inputs)) return false;
		else return $this->Inputs[$offset];
	}

	public function GetInputs() {
	/*//
	hand off the entire input array.
	//*/

		return $this->Inputs;
	}

	public function GetOption($name) {
	/*//
	fetch the specified option input value by name.
	//*/

		if(!array_key_exists($name,$this->Options)) return false;
		else return $this->Options[$offset];
	}

	public function GetOptions() {
	/*//
	hand off the entire option array.
	//*/

		return $this->Options;
	}

	///////////////////////////////////////////////////////////////////////////
	///////////////////////////////////////////////////////////////////////////

	public function Quit($msg,$code=0) {
	/*//
	print a message and kill off the application.
	//*/

		echo $msg, PHP_EOL, PHP_EOL;
		exit($code); return;
	}

}

<?php

namespace Nether\Console;
use \Nether;
use \Exception;

class Client {

	protected $Inputs = [];
	/*//
	@type array
	a list of non-switched arguments that were found.
	//*/

	protected $Options = [];
	/*//
	@type array
	a list of switched arguments that were found.
	//*/

	protected $DefaultHandlerName = 'help';
	/*//
	@type string
	the name of the default handler if no input is given.
	//*/

	protected $Handlers = [];
	/*//
	@type array
	a list of callables for handling input.
	//*/

	////////////////////////////////
	////////////////////////////////

	public function
	__construct($opt=null) {
		$this->ParseCommandLine();
		return;
	}

	////////////////////////////////
	////////////////////////////////

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

	////////////////////////////////
	////////////////////////////////

	public function
	PrintMessage($msg='',$opt=null) {
	/*//
	@argv string Message, object Options
	@return self

	print a string to the terminal, automatically doing line wrapping. if the
	string has a prefix of white space, then that prefix will be appended
	to all the lines after line wrapping, all without breaking the specified
	width of the wrap.

	if prefix is Boolean True, then we will scoop off the prefix of whitespace
	from the beginning of the string and use it on each line that was wrapped.
	if was a string to start with, then we will just use that prefix.
	//*/

		$opt = new Nether\Object($opt,[
			'EOL'    => PHP_EOL,
			'Prefix' => true,
			'Width'  => 75
		]);

		////////
		////////

		// scoop a prefix off the start of the string.
		if($opt->Prefix === true)
		$opt->Prefix = preg_replace('/^([\s]*).*?$/','\1',$msg);

		////////
		////////

		// consider the prefix length.
		$opt->Width -= strlen($opt->Prefix);

		// wrap the text.
		$msg = wordwrap($msg,$opt->Width,$opt->EOL);

		// apply the prefix.
		$lines = explode($opt->EOL,$msg);
		foreach($lines as $k => $v) $lines[$k] = sprintf(
			'%s%s',
			$opt->Prefix,
			ltrim($v)
		);

		echo implode($opt->EOL,$lines), $opt->EOL;
		return $this;
	}

	public function PrintStrings() {
	/*//
	@argv string Input, ...
	takes an infinite number of string arguments and runs them with the
	PrintMessage method. imho just a bit cleaner to write when trying
	to produce things like help info in the terminal. see the nether-onescript
	bin file to see what i mean.
	//*/

		foreach(func_get_args() as $string)
		$this->PrintMessage($string);

		return $this;
	}

	////////////////////////////////
	////////////////////////////////

	public function
	GetInput($offset) {
	/*//
	fetch the specified input value by offset. if you want the 1st input then
	you will ask for 1. we will zero-pwn it internally.
	//*/

		--$offset;

		if(!array_key_exists($offset,$this->Inputs)) return false;
		else return $this->Inputs[$offset];
	}

	public function
	GetInputs() {
	/*//
	hand off the entire input array.
	//*/

		return $this->Inputs;
	}

	public function
	GetOption($name) {
	/*//
	fetch the specified option input value by name.
	//*/

		if(!array_key_exists($name,$this->Options)) return false;
		else return $this->Options[$name];
	}

	public function
	GetOptions() {
	/*//
	hand off the entire option array.
	//*/

		return $this->Options;
	}

	////////////////////////////////
	////////////////////////////////

	public function
	Quit($msg,$code=0) {
	/*//
	print a message and kill off the application.
	//*/

		echo $msg, PHP_EOL, PHP_EOL;
		exit($code); return;
	}

	////////////////////////////////
	////////////////////////////////

	public function
	SetHandler($cmd, callable $func) {
	/*//
	//*/

		$this->Handlers[$cmd] = $func;
		return $this;
	}

	public function
	SetDefaultHandlerName($cmd) {
	/*//
	//*/

		$this->DefaultHandlerName = $cmd;
		return $this;
	}

	public function
	Run($cmd=null) {
	/*//
	//*/

		if(!$cmd && !($cmd = $this->GetInput(1))) {
			if($this->DefaultHandlerName) {
				$cmd = $this->DefaultHandlerName;
			} else {
				echo "No input provided.", PHP_EOL;
				return -2;
			}
		}

		if(!array_key_exists($cmd,$this->Handlers)) {
			echo "No handler defined for `{$cmd}`.", PHP_EOL;
			return -1;
		}

		return call_user_func(function($cli,$func){
			return $func($cli);
		},$this,$this->Handlers[$cmd]);
	}

	////////////////////////////////
	////////////////////////////////

	public function
	MakeDirectory($dir) {
	/*//
	//*/

		$umask = umask(0);
		@mkdir($dir,0777,true);
		umask($umask);

		return is_dir($dir);
	}

}

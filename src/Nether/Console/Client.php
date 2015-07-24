<?php

namespace Nether\Console;
use \Nether;
use \Exception;

class Client {

	const ErrorNoInput = -1;
	const ErrorNoHandler = -2;

	////////
	////////

	protected $Inputs;
	/*//
	@type array
	a list of non-switched arguments that were found.
	//*/

	protected $Options;
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
		if(!array_key_exists('argv',$_SERVER))
		throw new Exception('register_argc_argv must be enabled');

		$data = static::ParseCommandArgs($_SERVER['argv'],true);
		$this->Inputs = $data['Inputs'];
		$this->Options = $data['Options'];

		return;
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
	SetHandler($cmd, callable $func) {
	/*//
	@argv string HandlerName, callable HandlerFunc
	//*/

		$this->Handlers[$cmd] = $func;
		return $this;
	}

	public function
	SetDefaultHandlerName($cmd) {
	/*//
	@argv string HandlerName
	//*/

		$this->DefaultHandlerName = $cmd;
		return $this;
	}

	public function
	Run($cmd=null) {
	/*//
	@argv string HandlerName
	@argv array CommandArgumentList

	if given a string it will execute the handler defined under as that name.
	if given an array then it will process that array as though it was the
	_SERVER['argv'] data and execute as if that was the original command line
	given to the object creation.
	//*/

		$restore = false;
		$return = 0;

		////////
		////////

		if(is_array($cmd)) {
			// rewrite our input and option data with this subcommand
			// that was given to us. execute as though this array defined
			// the original command input.

			$restore = [ 'Inputs'=>$this->Inputs, 'Options'=>$this->Options ];

			$data = static::ParseCommandArgs($cmd,false);
			$this->Inputs = $data['Inputs'];
			$this->Options = $data['Options'];

			$cmd = $this->GetInput(1);
		}

		////////
		////////

		if(!$cmd && !($cmd = $this->GetInput(1))) {
			if(!$this->DefaultHandlerName) {
				echo "No input provided.", PHP_EOL;
				return static::ErrorNoInput;

			}

			$cmd = $this->DefaultHandlerName;
		}

		////////
		////////

		$method = static::GetMethodFromCommand($cmd);
		try { $return = $this->Run_ByMethod($method); }
		catch(ClientHandlerException $e) {
			try { $return = $this->Run_ByCallable($cmd); }
			catch(ClientHandlerException $e) {
				echo "no handler or method found for {$cmd}", PHP_EOL;
				return static::ErrorNoHandler;
			}
		}

		////////
		////////

		if($restore) {
			// restore the original input data before continuing on so that
			// we could continue processing the cli input data if needed.
			$this->Inputs = $restore['Inputs'];
			$this->Options = $restore['Options'];
		}

		return $return;
	}

	protected function
	Run_ByCallable($cmd) {
	/*//
	//*/

		if(!array_key_exists($cmd,$this->Handlers))
		throw new ClientHandlerException("no handler found {$cmd}");

		return call_user_func(function($cli,$func){
			return $func($cli);
		},$this,$this->Handlers[$cmd]);
	}

	protected function
	Run_ByMethod($method) {
	/*//
	//*/

		if(!method_exists($this,$method))
		throw new ClientHandlerException("no method found {$method}");

		return call_user_func([$this,$method]);
	}

	////////////////////////////////
	////////////////////////////////

	static public function
	Quit($msg='',$code=0) {
	/*//
	@argv string Message, int ErrorCode default 0
	print a message and kill off the application.
	//*/

		if($msg) echo $msg, PHP_EOL, PHP_EOL;

		exit($code);
	}

	////////////////////////////////
	////////////////////////////////

	static public function
	Message($msg='',$opt=null) {
	/*//
	@argv string Message, object Options

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
		return;
	}

	static public function
	Messages() {
	/*//
	@argv string Input, ...
	takes an infinite number of string arguments and runs them with the
	PrintMessage method. imho just a bit cleaner to write when trying
	to produce things like help info in the terminal. see the nether-onescript
	bin file to see what i mean.
	//*/

		foreach(func_get_args() as $string)
		static::Message($string);
	}

	////////////////////////////////
	////////////////////////////////

	static public function
	ParseCommandArgs($data,$skipfirst=false) {
	/*//
	parse the command line into digestable components. we got lucky in that
	php already handled breaking up the command line statement into nice
	chunks, handling quotes and tokens and all that stuff. this means we
	can process each item as a potential option or input straight out. this
	also means we can reuse this fact later when we want to override what the
	command line was with our own commands.

	$data[ 'filename', 'input1', 'input2', 'input3', '--someopt=with a value' ]
	//*/

		$output = [ 'Inputs'=>[], 'Options'=>[] ];
		$option = null;

		foreach($data as $key => $segment) {
			if($key === 0 && $skipfirst) continue;

			if($option = static::ParseCommandOption($segment)) {
				$output['Options'][key($option)] = current($option);
			} else {
				$output['Inputs'][] = $segment;
			}
		}

		return $output;
	}

	static public function
	ParseCommandOption($input) {
	/*//
	@argv string Input
	@return array or false

	parse an option into digestable components. our command arguments are
	going to behave a little differently than most people are used to. for
	example, i am not going to allow "--option value" it must be
	"--option=value" - nor am i going to allow "-xzf" they must be separate
	so "-x -z -f" - for both ease of implementation and that i always thought
	blocks of them were dumb anyway.

	returns an assoc array if we identified an option, else returns false.
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

	static public function
	MakeDirectory($dir) {
	/*//
	@argv string Directory
	@return bool

	make a directory. returns if successful or not. allows you to
	blindly call it if it already exists to ensure it exists.
	//*/

		// if it already exists...
		if(is_dir($dir)) return true;

		// make it...
		$umask = umask(0);
		@mkdir($dir,0777,true);
		umask($umask);

		// find out if it was successful, lol.
		return is_dir($dir);
	}

	////////////////////////////////
	////////////////////////////////

	static public function
	GetMethodFromCommand($cmd) {
	/*//
	//*/

		return sprintf(
			'Handle%s',
			str_replace(' ','',ucwords(preg_replace('/[-_]/',' ',strtolower($cmd))))
		);
	}

}

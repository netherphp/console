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

	protected
	$ChainCommands = FALSE;
	/*//
	@type Bool
	if we should process commands until we run out, or just the first one that
	we encounter.
	//*/

	////////////////////////////////
	////////////////////////////////

	public function
	__Construct($Opt=NULL) {

		if(!array_key_exists('argv',$_SERVER))
		throw new Exception('register_argc_argv must be enabled');

		$Opt = new Nether\Object\Mapped($Opt,[
			'Argv' => $_SERVER['argv']
		]);

		$Data = static::ParseCommandArgs($Opt->Argv,TRUE);
		$this->Inputs = $Data['Inputs'];
		$this->Options = $Data['Options'];

		$this->__Ready();
		return;
	}

	protected function
	__Ready():
	Void {
	/*//
	prototype method for implementing things to do on object construction
	without overwriting the actual object constructor.
	//*/

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

		if(!array_key_exists($offset,$this->Inputs)) return NULL;
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
	GetOption($Name) {
	/*//
	fetch the specified option input value by name. if the name is longer than
	one character then we check case insensitive.
	//*/

		if(strlen($Name) > 1)
		$Name = strtolower($Name);

		if(array_key_exists($Name,$this->Options))
		return $this->Options[$Name];

		return NULL;
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
	WillChainCommands():
	Bool {
	/*//
	@get ->ChainCommands
	//*/

		return $this->ChainCommands;
	}

	public function
	SetChainCommands(Bool $State):
	self {
	/*//
	@set ->ChainCommands
	//*/

		$this->ChainCommands = $State;
		return $this;
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

	////////////////////////////////
	////////////////////////////////

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

			$cmd = $this->GetInputs();
		}

		////////
		////////

		if(!$cmd && !($cmd = $this->GetInput(1))) {
			if(!$this->DefaultHandlerName) {
				echo "No input provided.", PHP_EOL;
				return static::ErrorNoInput;

			}

			$cmd = [$this->DefaultHandlerName];
		}

		else {
			$cmd = $this->GetInputs();
		}

		////////
		////////

		$Commanded = FALSE;

		foreach($cmd as $cur) {
			$method = static::GetMethodFromCommand($cur);
			try {
				$return = $this->Run_ByMethod($method);
				$Commanded = TRUE;
			}
			catch(ClientHandlerException $e) {
				try {
					$return = $this->Run_ByCallable($cur);
					$Commanded = TRUE;
				}
				catch(ClientHandlerException $e) { }
			}

			if($Commanded && !$this->ChainCommands)
			break;
		}

		if(!$Commanded) {
			echo "no handler or method found for {$cur}", PHP_EOL;
			return static::ErrorNoHandler;
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

		if($this->Handlers[$cmd] instanceof \Closure) {
			$closure = $this->Handlers[$cmd]->BindTo($this);
			return $closure();
			unset($closure);

			// the php70 version of the above.
			// return $this->Handlers[$cmd]->Call($this);
		} else {
			return call_user_func(function($cli,$func){
				return $func($cli);
			},$this,$this->Handlers[$cmd]);
		}
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

		$opt = new Nether\Object\Mapped($opt,[
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
		if($opt->Width) {
			// consider the prefix length.
			$opt->Width -= strlen($opt->Prefix);

			// wrap the text.
			$msg = wordwrap($msg,$opt->Width,$opt->EOL);
		}

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

	static public function
	PrintLine($msg='',$opt=null) {
	/*//
	@arg string Input, ...
	consider this an alias of Message with line wrapping disabled by default.
	//*/

		$opt = new Nether\Object\Mapped($opt,[
			'Width' => false
		]);

		return static::Message($msg,$opt);
	}

	static public function
	Prompt(?String $Msg=NULL, ?String $Prompt=NULL):
	String {
	/*//
	ask the user a question and await a response.
	//*/

		if($Msg)
		echo $Msg, PHP_EOL;

		if($Prompt)
		echo $Prompt, ' ';

		$Result = trim(fgets(STDIN));
		echo PHP_EOL;

		return $Result;
	}

	static public function
	PromptEquals(?String $Msg=NULL, ?String $Prompt=NULL, String $Condition):
	Bool {
	/*//
	ask the user a question, and check that their response is a match in a
	case insensitive way, since this will mostly be used for y/n questions.
	//*/

		return (strtolower(static::Prompt($Msg,$Prompt)) === strtolower($Condition));
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
				$output['Options'] = array_merge(
					$output['Options'],
					$option
				);
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

		if(preg_match('/^(-{1,2})/',$input,$m)) {
			if($m[1] === '--')
			return static::ParseCommandOption_LongForm($input);

			elseif($m[1] === '-')
			return static::ParseCommandOption_ShortForm($input);
		}

		return false;
	}

	static protected function
	ParseCommandOption_LongForm($Input) {
	/*//
	parse the long option form of --option=value. options which are one char
	long are kept case sensitive. options longer than one char are made case
	insensitive.
	//*/

		$Opt = explode('=',$Input,2);
		$Opt[0] = ltrim($Opt[0],'-');

		if(strlen($Opt[0]) > 1)
		$Opt[0] = strtolower($Opt[0]);

		switch(count($Opt)) {
			case 1: {
				$Output[$Opt[0]] = TRUE;
				break;
			}
			case 2: {
				$Output[$Opt[0]] = trim($Opt[1]);
				break;
			}
		}

		return $Output;
	}

	static protected function
	ParseCommandOption_ShortForm($input) {
	/*//
	parse the short option form of -zomg=bbq (-z -o -m -g=bbq). short form
	are always one character long and therefore kept case sensitive.
	//*/


		$output = [];
		$value = false;
		$opt = explode('=',ltrim($input,'-'),2);

		// figure out what the last value was.
		if(count($opt) === 2) $value = trim($opt[1]);
		else $value = true;

		// break the options apart setting them true.
		foreach(str_split($opt[0]) as $letter)
		$output[$letter] = true;

		// if the parsing did not really work send false out.
		if(!count($output)) return false;

		// then write the optional value to the last argument.
		end($output); $output[key($output)] = $value; reset($output);

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

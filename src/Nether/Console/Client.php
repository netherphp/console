<?php

namespace Nether\Console;

use Nether;

use Closure;
use Exception;
use ReflectionClass;
use ReflectionMethod;
use ReflectionAttribute;
use Nether\Object\Datastore;

class Client {

	const
	ErrorNoInput   = -1,
	ErrorNoHandler = -2;

	////////
	////////

	protected
	Array $Inputs;

	protected
	Array $Options;

	protected
	String $DefaultHandlerName = 'help';

	protected
	Array $Handlers = [];

	protected
	Bool $ChainCommands = FALSE;

	protected
	Int $Cols = 80;

	protected
	Int $Rows = 20;

	////////////////////////////////
	////////////////////////////////

	public function
	__Construct($Opt=NULL) {
	/*//
	@date 2016-02-03
	//*/

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
	GetInput(Int $Offset) {
	/*//
	@date 2016-02-03
	fetch the specified input value by offset. if you want the 1st input then
	you will ask for 1. we will zero-pwn it internally.
	//*/

		--$Offset;

		if(!array_key_exists($Offset,$this->Inputs))
		return NULL;

		return $this->Inputs[$Offset];
	}

	public function
	GetInputs() {
	/*//
	@date 2016-02-03
	hand off the entire input array.
	//*/

		return $this->Inputs;
	}

	public function
	GetOption(String $Name) {
	/*//
	@date 2016-02-03
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
	@date 2016-02-03
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
	@date 2016-11-15
	//*/

		return $this->ChainCommands;
	}

	public function
	SetChainCommands(Bool $State):
	self {
	/*//
	@date 2016-11-15
	//*/

		$this->ChainCommands = $State;
		return $this;
	}

	////////////////////////////////
	////////////////////////////////

	public function
	SetHandler(String $Cmd, Callable $Func) {
	/*//
	@date 2016-02-03
	//*/

		$this->Handlers[$Cmd] = $Func;
		return $this;
	}

	public function
	SetDefaultHandlerName(String $Cmd) {
	/*//
	@date 2016-02-03
	//*/

		$this->DefaultHandlerName = $Cmd;
		return $this;
	}

	////////////////////////////////
	////////////////////////////////

	public function
	Run(Mixed $Cmd=null):
	Mixed {
	/*//
	@date 2016-02-03
	//*/

		$Restore = false;
		$Return = 0;

		////////

		if(is_array($Cmd)) {
			// rewrite our input and option data with this subcommand
			// that was given to us. execute as though this array defined
			// the original command input.

			$Restore = [ 'Inputs'=>$this->Inputs, 'Options'=>$this->Options ];

			$Data = static::ParseCommandArgs($Cmd,false);
			$this->Inputs = $Data['Inputs'];
			$this->Options = $Data['Options'];

			$Cmd = $this->GetInputs();
		}

		////////

		if(!$Cmd && !($Cmd = $this->GetInput(1))) {
			if(!$this->DefaultHandlerName) {
				static::Message('no input provided');
				return static::ErrorNoInput;
			}

			$Cmd = [$this->DefaultHandlerName];
		}

		else {
			$Cmd = $this->GetInputs();
		}

		////////

		$Commanded = FALSE;

		foreach($Cmd as $Cur) {
			$Method = static::GetMethodFromCommand($Cur);
			try {
				$Return = $this->Run_ByMethod($Method);
				$Commanded = TRUE;
			}
			catch(ClientHandlerException) {
				try {
					$Return = $this->Run_ByCallable($Cur);
					$Commanded = TRUE;
				}
				catch(ClientHandlerException) { }
			}

			if($Commanded && !$this->ChainCommands)
			break;
		}

		if(!$Commanded) {
			echo "no handler or method found for {$Cur}", PHP_EOL;
			return static::ErrorNoHandler;
		}

		////////

		if($Restore) {
			// restore the original input data before continuing on so that
			// we could continue processing the cli input data if needed.
			$this->Inputs = $Restore['Inputs'];
			$this->Options = $Restore['Options'];
		}

		return $Return;
	}

	protected function
	Run_ByCallable(String $Cmd):
	Mixed {
	/*//
	@date 2016-02-03
	//*/

		if(!array_key_exists($Cmd,$this->Handlers))
		throw new ClientHandlerException("no handler found {$Cmd}");

		if(!is_callable($this->Handlers[$Cmd]))
		throw new ClientHandlerException("handler {$Cmd} is not callable");

		////////

		if($this->Handlers[$Cmd] instanceof Closure)
		return ($this->Handlers[$Cmd]->BindTo($this))();

		return call_user_func(
			(fn(Client $Client, Callable $Func) => $Func($Client)),
			$this,
			$this->Handlers[$Cmd]
		);
	}

	protected function
	Run_ByMethod(String $Method):
	Mixed {
	/*//
	@date 2016-02-03
	//*/

		if(!method_exists($this,$Method))
		throw new ClientHandlerException("no method found {$Method}");

		return call_user_func([$this,$Method]);
	}

	////////////////////////////////
	////////////////////////////////

	static public function
	Quit(String $Msg='', Int $Code=0):
	Void {
	/*//
	@date 2021-01-26
	@argv string Message, int ErrorCode default 0
	print a message and kill off the application.
	//*/

		if($Msg === '' && $Code !== 0)
		$Msg = static::Quit_GetMessageFromAttributes($Code);

		if($Msg)
		echo $Msg, PHP_EOL, PHP_EOL;

		exit($Code);
		return;
	}

	static protected function
	Quit_GetMessageFromAttributes(Int $Code):
	String {

		$Stack = (new Exception)->GetTrace();

		if(!array_key_exists(2,$Stack))
		return '';

		$Method = new ReflectionMethod($Stack[2]['class'],$Stack[2]['function']);
		$Attribs = (
			(new Datastore($Method->GetAttributes()))
			->Remap(fn(ReflectionAttribute $Val) => $Val->NewInstance())
			->Filter(fn(Object $Val) => (($Val instanceof Nether\Console\Meta\Error) && ($Val->GetCode() === $Code)))
			->Revalue()
		);

		if($Attribs->Count() === 0)
		return 'ERROR: Unknown';

		return "ERROR: {$Attribs[0]->GetText()}";
	}

	static public function
	EndOfLine(Int $Code=0, Array $Dataset=[]):
	Void {
	/*//
	@date 2021-01-14
	quit the app using just an error code. error messages will be fetched
	from the method attributes.
	//*/

		$Message = '';
		$Key = NULL;
		$Val = NULL;

		////////

		if($Code !== 0) {
			$Message = static::Quit_GetMessageFromAttributes($Code);

			foreach($Dataset as $Key => $Val)
			$Message = str_replace(
				"{\${$Key}}", $Val,
				$Message
			);
		}

		vprintf($Message,$Dataset);
		echo PHP_EOL;

		exit($Code);
	}

	////////////////////////////////
	////////////////////////////////

	static public function
	Message(String $Msg='', Array|Object $Opt=NULL) {
	/*//
	@date 2016-02-03

	print a string to the terminal, automatically doing line wrapping. if the
	string has a prefix of white space, then that prefix will be appended
	to all the lines after line wrapping, all without breaking the specified
	width of the wrap.

	if prefix is Boolean True, then we will scoop off the prefix of whitespace
	from the beginning of the string and use it on each line that was wrapped.
	if was a string to start with, then we will just use that prefix.
	//*/

		$Opt = new Nether\Object\Mapped($Opt,[
			'EOL'    => PHP_EOL,
			'Prefix' => TRUE,
			'Width'  => NULL
		]);

		if($Opt->Width === NULL)
		$Opt->Width = static::GetTerminalSize()[0];

		////////

		// scoop a prefix off the start of the string.
		if($Opt->Prefix === TRUE)
		$Opt->Prefix = preg_replace('/^([\s]*).*?$/','\1',$Msg);

		////////

		if($Opt->Width) {
			// consider the prefix length.
			$Opt->Width -= strlen($Opt->Prefix);

			// wrap the text.
			$Msg = wordwrap($Msg,$Opt->Width,$Opt->EOL);
		}

		// apply the prefix.

		$Lines = explode($Opt->EOL,$Msg);

		foreach($Lines as &$Line)
		$Line = sprintf('%s%s', $Opt->Prefix, ltrim($Line));

		echo implode($Opt->EOL,$Lines), $Opt->EOL;
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

		$Width = static::GetTerminalSize()[0];
		$String = NULL;

		foreach(func_get_args() as $String)
		static::Message($String,[
			'Width' => $Width
		]);
	}

	static public function
	PrintLine(String $Msg='', Array|Object $Opt=null) {
	/*//
	@date 2016-02-03
	consider this an alias of Message with line wrapping disabled by default.
	//*/

		$Opt = new Nether\Object\Mapped($Opt,[
			'Width' => FALSE
		]);

		return static::Message($Msg,$Opt);
	}

	static public function
	Prompt(?String $Msg=NULL, ?String $Prompt=NULL):
	String {
	/*//
	@date 2016-08-11
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
	@date 2016-08-11
	ask the user a question, and check that their response is a match in a
	case insensitive way, since this will mostly be used for y/n questions.
	//*/

		return (strtolower(static::Prompt($Msg,$Prompt)) === strtolower($Condition));
	}

	////////////////////////////////
	////////////////////////////////

	static public function
	ParseCommandArgs(Array $Data, Bool $SkipFirst=FALSE):
	Array {
	/*//
	@date 2016-02-03

	parse the command line into digestable components. we got lucky in that
	php already handled breaking up the command line statement into nice
	chunks, handling quotes and tokens and all that stuff. this means we
	can process each item as a potential option or input straight out. this
	also means we can reuse this fact later when we want to override what the
	command line was with our own commands.

	$Data[ 'filename', 'input1', 'input2', 'input3', '--someopt=with a value' ]
	//*/

		$Output = [ 'Inputs'=>[], 'Options'=>[] ];
		$Option = NULL;

		foreach($Data as $Key => $Segment) {
			if($Key === 0 && $SkipFirst)
			continue;

			if($Option = static::ParseCommandOption($Segment))
			$Output['Options'] = array_merge($Output['Options'], $Option);

			else
			$Output['Inputs'][] = $Segment;
		}

		return $Output;
	}

	static public function
	ParseCommandOption(String $Input):
	?Array {
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

		$Match = NULL;

		if(preg_match('/^(-{1,2})/',$Input,$Match)) {
			if($Match[1] === '--')
			return static::ParseCommandOption_LongForm($Input);

			elseif($Match[1] === '-')
			return static::ParseCommandOption_ShortForm($Input);
		}

		return NULL;
	}

	static protected function
	ParseCommandOption_LongForm(String $Input):
	?Array {
	/*//
	@date 2021-02-03
	parse the long option form of --option=value. options which are one char
	long are kept case sensitive. options longer than one char are made case
	insensitive.
	//*/

		$Output = [];

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
	ParseCommandOption_ShortForm(String $Input):
	?Array {
	/*//
	@date 2021-02-03
	parse the short option form of -zomg=bbq (-z -o -m -g=bbq). short form
	are always one character long and therefore kept case sensitive.
	//*/

		$Output = [];
		$Value = FALSE;
		$Opt = explode('=',ltrim($Input,'-'),2);

		// figure out what the last value was.

		if(count($Opt) === 2)
		$Value = trim($Opt[1]);

		else
		$Value = TRUE;

		// break the options apart setting them true.

		foreach(str_split($Opt[0]) as $Letter)
		$Output[$Letter] = TRUE;

		// if the parsing did not really work send false out.

		if(!count($Output))
		return NULL;

		// then write the optional value to the last argument.

		end($Output);
		$Output[key($Output)] = $Value;
		reset($Output);

		return $Output;
	}

	////////////////////////////////
	////////////////////////////////

	static public function
	GetTerminalSize():
	Array {
	/*//
	@date 2021-01-04
	//*/

		$Output = [80,20];
		$Size = NULL;

		$Size = match(strtoupper(PHP_OS)) {
			'LINUX' => static::GetTerminalSize_Linux(),
			'WINNT' => static::GetTerminalSize_Windows(),
			default => NULL
		};

		if(is_array($Size) && count($Size) === 2)
		$Output = $Size;

		return $Output;
	}

	static protected function
	GetTerminalSize_Linux():
	?Array {
	/*//
	@date 2021-01-14
	//*/

		$Output = NULL;
		$Size = explode(' ',trim(shell_exec('stty size')));

		if($Size && count($Size) >= 2)
		list($Output[1],$Output[0]) = $Size;

		return $Output;
	}

	static protected function
	GetTerminalSize_Windows():
	?Array {
	/*//
	@date 2021-01-14
	//*/

		$Output = NULL;
		$Result = shell_exec('mode CON');
		$Match = NULL;
		$Key = NULL;

		preg_match_all('/([a-z0-9]+):\h+(\d+)/i',$Result,$Match);

		foreach(array_keys($Match[1]) as $Key) {
			if($Match[1][$Key] === 'Lines')
			$Output[1] = $Match[2][$Key];
			elseif($Match[1][$Key] === 'Columns')
			$Output[0] = $Match[2][$Key];
		}

		return $Output;
	}

	static public function
	MakeDirectory(String $Dir):
	Bool {
	/*//
	@date 2016-02-03
	make a directory. returns if successful or not. allows you to
	blindly call it if it already exists to ensure it exists.
	//*/

		// if it already exists...

		if(is_dir($Dir))
		return TRUE;

		// make it...

		if(php_sapi_name() === 'cli') {
			$Mask = umask(0);
			@mkdir($Dir,0777,true);
			umask($Mask);
		}

		else
		@mkdir($Dir,0777,true);

		// find out if it was successful, lol.

		return is_dir($Dir);
	}

	////////////////////////////////
	////////////////////////////////

	static public function
	GetMethodFromCommand(String $Cmd):
	String {
	/*//
	@date 2016-02-04
	//*/

		return sprintf(
			'Handle%s',
			str_replace(' ','',ucwords(preg_replace(
				'/[-_]/', ' ',
				strtolower($Cmd)
			)))
		);
	}

	static public function
	GetCommandFromMethod(String $Method):
	String {
	/*//
	@date 2021-01-05
	//*/

		$Command = preg_replace('/^Handle/','',$Method);
		$Command = strtolower(preg_replace('/([a-z0-9])([A-Z])/ms','\\1-\\2',$Command));

		return $Command;
	}

	static public function
	GetCommandName():
	String {
	/*//
	@date 2021-01-05
	//*/

		return basename($_SERVER['PHP_SELF']);
	}

	////////////////////////////////
	////////////////////////////////

	#[Nether\Console\Meta\Subcommand]
	#[Nether\Console\Meta\Info('Displays this help info.')]
	public function
	HandleHelp():
	Int {
	/*//
	@date 2021-01-05
	//*/

		$Command = static::GetCommandName();
		$Class = new ReflectionClass(static::class);
		$Methods = NULL;
		$Lines = [];

		// build a list of all the methods that have the subcommand attribute
		// attached to them to process as console commands.

		$Methods = (
			(new Datastore($Class->GetMethods()))
			->Each(
				fn(ReflectionMethod $Method) =>
				$Method->Attributes = new Datastore($Method->GetAttributes())
			)
			->Filter(
				fn($Method) =>
				count($Method->Attributes->Distill(
					fn(ReflectionAttribute $Attrib) =>
					$Attrib->GetName() === Nether\Console\Meta\Subcommand::class
				))
			)
			->Each(
				fn($Method) =>
				$Method->Attributes->Remap(
					fn(ReflectionAttribute $A) =>
					$A->NewInstance()->BuildFromReflection($Method)
				)
			)
		);

		// start compiling the help text.

		$F = new Nether\Console\TerminalFormatter;
		$Lines[] = "{$F->BoldWhite()}USAGE: {$Command} <command> <options>{$F->Reset()}";
		$Lines[] = "";

		$Methods->Each(function($Method) use(&$Lines,&$F) {
			$Subcommand = $Method->Attributes->Distill(fn($A) => $A instanceof Meta\Subcommand)->Revalue()->Get(0);
			$Info = $Method->Attributes->Distill(fn($A) => $A instanceof Meta\Info)->Revalue()->Get(0);
			$Args = $Method->Attributes->Distill(fn($A) => $A instanceof Meta\SubcommandArg);
			$Options = $Method->Attributes->Distill(fn($A) => $A instanceof Meta\SubcommandOption);

			$Option = NULL;
			$Prefix = "  ";


			// subcommand definition.

			$Lines[] = "{$Prefix}{$F->BoldYellow($Subcommand->GetNameArgsOptions($Args,$Options))}";
			$Lines[] = ($Info instanceof Meta\Info)?("{$Prefix}{$Info}"):("{$Prefix}No info available.");
			$Lines[] = "";

			// subcommand option list.

			foreach($Options as $Option) {
				$Lines[] = "{$Prefix}{$Prefix}{$F->Yellow2($Option->GetNameValue())}";

				if($Option->GetText())
				$Lines[] = "{$Prefix}{$Prefix}{$Option->GetText()}";

				$Lines[] = "";
			}
			return;
		});

		static::Messages(...$Lines);
		return 0;
	}

}

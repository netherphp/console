<?php

namespace Nether\Console;
use Nether;

use Nether\Object\Datastore;
use Nether\Object\Prototype\MethodInfo;
use Nether\Object\Package\MethodInfoPackage;
use Nether\Console\Struct\CommandArgs;
use Nether\Console\Error\RegisterArgcArgvUndefined;

class Client {

	use
	MethodInfoPackage;

	public string
	$Command = 'help';

	public string
	$Name;

	public Datastore
	$Commands;

	public CommandArgs
	$Args;

	public TerminalFormatter
	$Formatter;

	public string
	$ColourPrimary = 'BoldYellow';

	public string
	$ColourSecondary = 'Yellow';

	public function
	__Construct(?array $Argv=NULL) {

		if($Argv === NULL) {
			if(!isset($_SERVER['argv']))
			throw new RegisterArgcArgvUndefined;

			$Argv = $_SERVER['argv'];
		}

		$this
		->BuildCommandIndex()
		->ParseArguments($Argv);

		$this->Formatter = new TerminalFormatter;

		$this->OnReady();
		return;
	}

	protected function
	OnReady():
	void {

		return;
	}

	////////////////////////////////////////////////////////////////
	////////////////////////////////////////////////////////////////

	protected function
	BuildCommandIndex():
	static {

		$this->Commands = (
			(new Datastore(static::GetMethodsWithAttribute(Meta\Command::class)))
			->RemapKeys(
				fn(string $Key, MethodInfo $Method)
				=> [
					$Method->Attributes[Meta\Command::class]->Name
					=> $Method
				]
			)
		);

		$this->Commands->Sort();

		return $this;
	}

	protected function
	ParseArguments(array $Argv):
	static {

		$this->Args = Util::ParseCommandArgs($Argv, FALSE);
		$this->Name = $this->Args->Inputs->Shift();
		$this->Command = strtolower($this->Args->Inputs->Shift() ?? 'help');

		return $this;
	}

	////////////////////////////////////////////////////////////////
	////////////////////////////////////////////////////////////////

	public function
	GetInput(int $Key):
	mixed {

		$Key -= 1;

		return $this->Args->Inputs[$Key];
	}

	public function
	GetOption(string $Key):
	mixed {

		$Key = strtolower($Key);

		return $this->Args->Options[$Key];
	}

	public function
	GetQuitMessage(int $Err):
	string {

		$Command = $this->Commands[$this->Command];
		$Errors = $Command->GetAttribute(Nether\Console\Meta\Error::class);
		$Error = NULL;
		$Message = '';

		if($Err !== 0)
		$Message .= "ERROR({$Err}): ";

		if($Errors) {
			$Errors = (
				(new Datastore(is_array($Errors) ? $Errors : [$Errors]))
				->Filter(
					fn(Nether\Console\Meta\Error $Error)
					=> $Error->Code === $Err
				)
			);

			foreach($Errors as $Error) {
				$Message .= $Error->Text;
				break;
			}
		}

		return $Message;
	}

	public function
	FormatPrimary(string $Text):
	string {

		return $this->Formatter->{$this->ColourPrimary}($Text);
	}

	public function
	FormatSecondary(string $Text):
	string {

		return $this->Formatter->{$this->ColourSecondary}($Text);
	}

	public function
	Run():
	int {

		$Method = NULL;
		$Result = NULL;
		$Message = NULL;
		$Code = NULL;

		////////

		if(!$this->Commands->HasKey($this->Command))
		return $this->HandleCommandHelp();

		////////

		$Method = $this->Commands[$this->Command];

		try {
			$Result = $this->{$Method->Name}();
		}

		catch(Error\QuitException $Quit) {
			$Message = $Quit->GetMessage();
			$Code = $Quit->GetCode();

			if($Message)
			echo $Message, PHP_EOL;

			return $Code;
		}

		return $Result;
	}

	public function
	Quit(int $Err, ...$MsgTokens):
	never {

		throw new Error\QuitException(
			$Err, vsprintf($this->GetQuitMessage($Err), $MsgTokens)
		);
	}

	public function
	PrintLn(?string $Line=NULL):
	static {

		echo ($Line ?? ''), PHP_EOL;
		return $this;
	}

	public function
	Prompt(?string $Msg=NULL, ?string $Prompt=NULL, mixed $Input=STDIN):
	string {

		if($Msg !== NULL)
		echo $Msg, PHP_EOL;

		if($Prompt !== NULL)
		echo $Prompt, ' ';

		$Result = trim(fgets($Input));
		echo PHP_EOL;

		return $Result;
	}

	public function
	PromptEquals(?string $Msg=NULL, ?string $Prompt=NULL, string $Condition='y', mixed $Input=STDIN):
	bool {

		$Result = $this->Prompt($Msg, $Prompt, $Input);

		return ($Result === $Condition);
	}

	public function
	ExecuteCommandLine(string $Command, bool $Silent=FALSE):
	Struct\CommandLineUtil {

		if(!$Silent)
		$this->PrintLn(sprintf(
			'%s %s',
			$this->FormatPrimary('[ExecuteCommandLine]'),
			$Command
		));

		$CLI = new Struct\CommandLineUtil($Command);
		$CLI->Run();

		return $CLI;
	}

	public function
	Sudo():
	bool {
	/*//
	return true if a privilege escilaton was performed. return false if we
	are already good to go.
	//*/

		$IsAdmin = (posix_getuid() === 0);
		$SudoPath = trim(`which sudo`);

		if($IsAdmin)
		return FALSE;

		return pcntl_exec(
			$SudoPath,
			$this->Args->Source
		);
	}

	////////////////////////////////////////////////////////////////
	////////////////////////////////////////////////////////////////

	#[Nether\Console\Meta\Command('help')]
	#[Nether\Console\Meta\Arg('command', 'Only show help for specific command.')]
	#[Nether\Console\Meta\Toggle('--verbose', 'Shows all of the helpful.')]
	#[Nether\Console\Meta\Info('Display this help.')]
	public function
	HandleCommandHelp():
	int {

		$Picked = $this->GetInput(1);
		$Verbose = $this->GetOption('verbose') ?? FALSE;
		$Method = NULL;
		$Command = NULL;
		$Args = NULL;
		$Options = NULL;
		$Info = NULL;

		if($Picked !== NULL)
		$Verbose = TRUE;

		////////

		printf(
			'%1$s %2$s <command> <args>%3$s%3$s',
			$this->Formatter->{$this->ColourPrimary}('USAGE:'),
			$this->Name,
			PHP_EOL
		);

		if(!$Picked) {
			printf(
				'%s - view help for specific command.%s',
				$this->FormatSecondary('help <command>'),
				PHP_EOL
			);

			printf(
				'%s - view all help for all commands.%s%s',
				$this->FormatSecondary('help --verbose'),
				PHP_EOL,
				PHP_EOL
			);
		}

		foreach($this->Commands as $Method) {
			/** @var MethodInfo $Method */

			$Command = $Method->GetAttribute(Nether\Console\Meta\Command::class);
			$Args = $Method->GetAttribute(Nether\Console\Meta\Arg::class);
			$Options = $Method->GetAttribute(Nether\Console\Meta\Option::class);
			$Toggles = $Method->GetAttribute(Nether\Console\Meta\Toggle::class);
			$Values = $Method->GetAttribute(Nether\Console\Meta\Value::class);
			$Info = $Method->GetAttribute(Nether\Console\Meta\Info::class);
			$Indent = "  ";

			if($Picked && $Command->Name !== $Picked)
			continue;

			if(!$Picked && $Command->Hide)
			continue;

			////////

			if($Options)
			$Options = new Datastore(is_array($Options) ? $Options : [$Options]);
			else
			$Options = new Datastore;

			if($Args)
			$Args = new Datastore(is_array($Args) ? $Args : [$Args]);

			////////

			if($Toggles)
			$Options->MergeRight(is_array($Toggles) ? $Toggles : [$Toggles]);

			if($Values)
			$Options->MergeRight(is_array($Values) ? $Values : [$Values]);

			$Options
			->Sort(function(Nether\Console\Meta\Option $A, Nether\Console\Meta\Option $B){

				return ltrim($A->Name, '!') <=> ltrim($B->Name, '!');
			});

			printf(
				'%s%s%s%s',
				$Indent,
				$this->Formatter->{$this->ColourPrimary}($Command->Name),
				($Args ? $Args->Map(fn($Val)=> " <{$Val->Name}>")->Join('') : ''),
				str_repeat(PHP_EOL, 2)
			);

			if($Info || (!$Info && !$Options->Count()))
			printf(
				'%s%s%s',
				str_repeat($Indent, 2),
				($Info ? $Info->Text : 'No info provided.'),
				str_repeat(PHP_EOL, 2)
			);

			if($Verbose && $Options)
			foreach($Options as $Option) {
				printf(
					'%s%s%s%s',
					str_repeat($Indent, 2),
					$this->Formatter->{$this->ColourSecondary}($Option->Name),
					($Option->TakesValue ? '=<…>' : ''),
					str_repeat(PHP_EOL, ($Option->Text ? 1 : 2))
				);

				if($Option->Text)
				printf(
					'%s%s%s',
					str_repeat($Indent, 3),
					$Option->Text,
					str_repeat(PHP_EOL, 2)
				);
			}

		}

		return 0;
	}

}

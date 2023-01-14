<?php

namespace Nether\Console;
use Nether;

use Throwable;
use Nether\Object\Datastore;
use Nether\Object\Prototype\ClassInfo;
use Nether\Object\Prototype\MethodInfo;
use Nether\Object\Package\ClassInfoPackage;
use Nether\Object\Package\MethodInfoPackage;
use Nether\Console\Struct\CommandArgs;
use Nether\Console\Error\RegisterArgcArgvUndefined;

class Client {

	use
	ClassInfoPackage,
	MethodInfoPackage;

	const
	AppName    = 'AppName',
	AppDesc    = 'A CLI app.',
	AppVersion = '0.0.0';

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

	protected function
	OnRun():
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
	HasOption(string $Key):
	mixed {

		$Key = strtolower($Key);

		return $this->Args->Options->HasKey($Key);
	}

	public function
	GetQuitMessage(int $Err):
	string {

		$Command = $this->Commands[$this->Command];
		$Error = NULL;
		$Message = '';

		// build a list of errors defined on the class itself that are
		// consistent across methods, and then overwrite with any specific
		// errors defined by the method.

		$Errors = array_merge(
			(
				$this
				->GetClassInfo(static::class)
				->GetAttributes(Nether\Console\Meta\Error::class)
			),
			$Command->GetAttributes(Nether\Console\Meta\Error::class)
		);

		////////

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
			$this->OnRun();
			$Result = $this->{$Method->Name}();
		}

		catch(Error\QuitException $Quit) {
			$Message = $Quit->GetMessage();
			$Code = $Quit->GetCode();

			if($Message)
			echo $Message, PHP_EOL;

			return $Code;
		}

		catch(Throwable $Err) {
			printf(
				'[UnmanagedException] %s %s',
				$Err::class,
				$Err->GetMessage()
			);

			return $Code ?? -1;
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
	FormatLn(?string $Fmt=NULL, ...$Argv):
	static {

		printf($Fmt, ...$Argv);
		echo PHP_EOL;

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

	#[Meta\Command('help')]
	#[Meta\Arg('command', 'Only show help for specific command.')]
	#[Meta\Toggle('--verbose', 'Shows all of the helpful.')]
	#[Meta\Info('Display this help.')]
	public function
	HandleCommandHelp():
	int {

		$Picked = $this->GetInput(1);
		$Verbose = $this->GetOption('verbose') ?? FALSE;
		$Version = $this->GetOption('version') ?? FALSE;
		$Class = $this->GetClassInfo();
		$Method = NULL;
		$Command = NULL;
		$Args = NULL;
		$Options = NULL;
		$Info = NULL;

		if($Picked !== NULL)
		$Verbose = TRUE;

		if($Version) {
			$this->PrintLn(static::AppVersion);
			return 0;
		}

		////////

		if(!$Picked) {
			$this
			->FormatLn('%s %s', static::AppName, static::AppVersion)
			->PrintLn(static::AppDesc)
			->PrintLn();
		}

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

			$Command = $Method->GetAttribute(Meta\Command::class);
			$Info = $Method->GetAttribute(Meta\Info::class);
			$Indent = "  ";

			$Args = array_merge(
				$Class->GetAttributes(Meta\Arg::class),
				$Method->GetAttributes(Meta\Arg::class)
			);

			$Options = array_merge(
				$Class->GetAttributes(Meta\Option::class),
				$Method->GetAttributes(Meta\Option::class)
			);

			$Toggles = array_merge(
				$Class->GetAttributes(Meta\Toggle::class),
				$Method->GetAttributes(Meta\Toggle::class)
			);

			$Values = array_merge(
				$Class->GetAttributes(Meta\Value::class),
				$Method->GetAttributes(Meta\Value::class)
			);

			if($Picked && $Command->Name !== $Picked)
			continue;

			if(!$Picked && $Command->Hide)
			continue;

			////////

			if($Options)
			$Options = new Datastore($Options);
			else
			$Options = new Datastore;

			if($Args)
			$Args = new Datastore($Args);

			////////

			if($Toggles)
			$Options->MergeRight($Toggles);

			if($Values)
			$Options->MergeRight($Values);

			$Options->Sort(
				fn(Meta\Option $A, Meta\Option $B)
				=> $A->Name <=> $B->Name
			);

			////////

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

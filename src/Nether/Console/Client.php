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

	protected string
	$Name;

	protected Datastore
	$Commands;

	protected CommandArgs
	$Args;

	protected TerminalFormatter
	$Formatter;

	protected string
	$ColourPrimary = 'BoldYellow';

	protected string
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
	Run():
	static {

		$Method = NULL;

		if($this->Commands->HasKey($this->Command)) {
			$Method = $this->Commands[$this->Command];

			if(method_exists($this, $Method->Name))
			$this->{$Method->Name}();
		}

		else {
			$this->HandleCommandHelp();
		}

		return $this;
	}

	public function
	Quit(int $Err):
	void {

		$Command = $this->Commands[$this->Command];
		$Errors = $Command->GetAttribute(Nether\Console\Meta\Error::class);
		$Error = NULL;
		$Message = '';

		if($Err !== 0)
		$Message .= 'ERROR: ';

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

		echo $Message, PHP_EOL;
		exit($Err);

		return;
	}

	////////////////////////////////////////////////////////////////
	////////////////////////////////////////////////////////////////

	#[Nether\Console\Meta\Command('help')]
	#[Nether\Console\Meta\Info('Display this help.')]
	public function
	HandleCommandHelp():
	int {

		$Method = NULL;
		$Command = NULL;
		$Args = NULL;
		$Options = NULL;
		$Info = NULL;

		printf(
			'%1$s %2$s <command> <args>%3$s%3$s',
			$this->Formatter->{$this->ColourPrimary}('USAGE:'),
			$this->Name,
			PHP_EOL
		);

		foreach($this->Commands as $Method) {
			/** @var MethodInfo $Method */

			$Command = $Method->GetAttribute(Nether\Console\Meta\Command::class);
			$Args = $Method->GetAttribute(Nether\Console\Meta\Arg::class);
			$Options = $Method->GetAttribute(Nether\Console\Meta\Option::class);
			$Toggles = $Method->GetAttribute(Nether\Console\Meta\Toggle::class);
			$Values = $Method->GetAttribute(Nether\Console\Meta\Value::class);
			$Info = $Method->GetAttribute(Nether\Console\Meta\Info::class);
			$Indent = "  ";

			if($Command->Name === 'help')
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
				($Args ? $Args->Map(fn($Val)=> " <{$Val}>")->Join('') : ''),
				str_repeat(PHP_EOL, 2)
			);

			if($Info || (!$Info && !$Options->Count()))
			printf(
				'%s%s%s',
				str_repeat($Indent, 2),
				($Info ? $Info->Text : 'No info provided.'),
				str_repeat(PHP_EOL, 2)
			);

			if($Options)
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

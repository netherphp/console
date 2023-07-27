<?php

namespace Nether\Console;

use Nether\Common;

use Throwable;

class Client {

	use
	Common\Package\ClassInfoPackage,
	Common\Package\MethodInfoPackage;

	////////////////////////////////////////////////////////////////
	////////////////////////////////////////////////////////////////

	const
	FmtPrime     = 'Primary',
	FmtPrimeAlt  = 'PrimaryAlt',
	FmtAccent    = 'Accent',
	FmtAccentAlt = 'AccentAlt',
	FmtError     = 'Error',
	FmtErrorAlt  = 'ErrorAlt',
	FmtOK        = 'OK',
	FmtOKAlt     = 'OKAlt',
	FmtMuted     = 'Muted',
	FmtMutedAlt  = 'MutedAlt';

	const
	FmtPresets = [
		'Primary'      => [ 'Bold'=> TRUE, 'Colour'=> '#F6684E' ],
		'PrimaryAlt'   => [ 'Bold'=> TRUE, 'Colour'=> '#FAA99A' ],
		'Secondary'    => [ 'Bold'=> TRUE, 'Colour'=> '#E3C099' ],
		'SecondaryAlt' => [ 'Bold'=> TRUE, 'Colour'=> '#EFDBC5' ],
		'Error'        => [ 'Bold'=> TRUE, 'Colour'=> '#E17B7B' ],
		'ErrorAlt'     => [ 'Bold'=> TRUE, 'Colour'=> '#E17B7B' ],
		'OK'           => [ 'Bold'=> TRUE, 'Colour'=> '#4EA125' ],
		'OKAlt'        => [ 'Bold'=> TRUE, 'Colour'=> '#A2D181' ],
		'Muted'        => [ 'Colour'=> '#666666' ],
		'MutedAlt'     => [ 'Colour'=> '#AAAAAA' ]
	];

	////////////////////////////////////////////////////////////////
	////////////////////////////////////////////////////////////////

	public Meta\Application
	$AppInfo;

	public string
	$Command = 'help';

	public string
	$Name;

	public Common\Datastore
	$Commands;

	public Struct\CommandArgs
	$Args;

	public string
	$StatusEmoji = 'square';

	////////////////////////////////////////////////////////////////
	// DEPRECATED //////////////////////////////////////////////////

	const
	AppName    = 'AppName',
	AppDesc    = 'A CLI app.',
	AppVersion = '0.0.0',
	AppDebug   = FALSE;

	#[Common\Meta\Deprecated('2023-07-26')]
	public TerminalFormatter
	$Formatter;

	#[Common\Meta\Deprecated('2023-07-26')]
	public string
	$ColourPrimary = 'BoldYellow';

	#[Common\Meta\Deprecated('2023-07-26')]
	public string
	$ColourSecondary = 'Yellow';

	////////////////////////////////////////////////////////////////
	////////////////////////////////////////////////////////////////

	public function
	__Construct(?array $Argv=NULL) {

		if($Argv === NULL) {
			if(!isset($_SERVER['argv']))
			throw new Error\RegisterArgcArgvUndefined;

			$Argv = $_SERVER['argv'];
		}

		$this
		->ReadAppInfo()
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
	ReadAppInfo():
	static {

		$ClassInfo = static::GetClassInfo();
		$AttrAppl = $ClassInfo->GetAttribute(Meta\Application::class);
		$AttrInfo = $ClassInfo->GetAttribute(Meta\Info::class);

		// bring in the main application information from the application
		// attribute.

		if(!$AttrAppl)
		$AttrAppl = new Meta\Application(
			Name: static::AppName,
			Version: static::AppVersion,
			AutoCmd: NULL
		);

		$this->AppInfo = $AttrAppl;

		// bring in the application description from the info attribute.

		if($AttrInfo)
		$this->AppInfo->Desc = $AttrInfo->Text;

		////////

		return $this;
	}

	protected function
	BuildCommandIndex():
	static {

		$this->Commands = (
			(new Common\Datastore(static::GetMethodsWithAttribute(Meta\Command::class)))
			->RemapKeys(
				fn(string $Key, Common\Prototype\MethodInfo $Method)
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
		$this->Command = $this->Args->Inputs->Shift() ?? 'help';

		if($this->AppInfo->AutoCmd) {
			if($this->Commands->HasKey($this->AppInfo->AutoCmd))
			(Common\Datastore::FromArray($this->Commands[$this->AppInfo->AutoCmd]->Attributes))
			->Filter(fn(mixed $A): bool => $A instanceof Meta\Command)
			->Each(fn(Meta\Command $A)=> $A->Hide = TRUE);

			if($this->Command !== 'help') {
				$this->Args->Inputs->Unshift($this->Command);
				$this->Command = $this->AppInfo->AutoCmd;
			}
		}

		$this->Command = strtolower($this->Command);

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
				->GetAttributes(Meta\Error::class)
			),
			$Command->GetAttributes(Meta\Error::class)
		);

		////////

		if($Err !== 0)
		$Message .= "ERROR({$Err}): ";

		if($Errors) {
			$Errors = (
				(new Common\Datastore(is_array($Errors) ? $Errors : [$Errors]))
				->Filter(
					fn(Meta\Error $Error)
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
	FormatErrorPrimary(string $Text):
	string {

		return $this->Formatter->BoldRed($Text);
	}

	public function
	FormatErrorSecondary(string $Text):
	string {

		return $this->Formatter->Red($Text);
	}

	public function
	GetColourForStatus(mixed $Status):
	Common\Units\Colour {

		$Colour = match(TRUE) {
			$Status === TRUE,
			$Status === 'OK',
			=> new Common\Units\Colour('#44CC44'),

			$Status === FALSE,
			$Status === 'ERROR',
			=> new Common\Units\Colour('#CC4444'),

			$Status === NULL,
			$Status === 'UNKNOWN'
			=> new Common\Units\Colour('#CCCCCC'),

			default
			=> new Common\Units\Colour('#ffffff')
		};

		return $Colour;
	}

	public function
	GetFormatForStatus(mixed $Status, string $Alt=NULL):
	array {

		$Output = match(TRUE) {

			$Status === TRUE,
			$Status === 'ok',
			=> [ 'Colour'=> new Common\Units\Colour('#44CC44') ],

			$Status === FALSE,
			$Status === 'error',
			=> [ 'Colour'=> new Common\Units\Colour('#CC4444') ],

			$Status === NULL,
			$Status === 'unknown'
			=> [ 'Colour'=> new Common\Units\Colour('#CCCCCC') ],

			////////

			$Status === 'primary'
			=> [ 'Colour'=> new Common\Units\Colour('#E4D060') ],

			default
			=> []
		};

		switch($Alt) {
			case 'alt1':
				($Output['Colour'])
				->Desaturate(44);
			break;
			case 'alt2':
				($Output['Colour'])
				->Desaturate(44)
				->Rotate(45);
			break;
			case 'alt3':
				($Output['Colour'])
				->Desaturate(44)
				->Rotate(-45);
			break;
		}

		return $Output;
	}

	static public function
	GetStatusEmoji(mixed $Status):
	string {

		if($Status === TRUE)
		return '🟢';

		if($Status === FALSE)
		return '🔴';

		if(is_int($Status))
		return ['⚫', '⚪', '🟤', '🔴', '🟠', '🟡', '🟢', '🔵', '🟣'][$Status];

		////////

		return '⚫';
	}

	public function
	Run():
	int {

		$Method = NULL;
		$Result = NULL;
		$Message = NULL;
		$Code = NULL;

		// if the command specified was not found then the app should bail
		// now with whatever helpful and informative stupids i end up
		// putting in the command attributes.

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
			$Message = $Err->GetMessage();
			$Code = $Err->GetCode();

			($this)
			->PrintLn()
			->PrintLn(sprintf(
				'%s %s',
				$this->Format("[{$this->AppInfo->Name}:UnhandledException]", static::FmtError),
				$Message
			))
			->PrintLn()
			->PrintLn($this->Format($Err->GetTraceAsString(), static::FmtMuted));

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
	PrintLn(?string $Line=NULL, int $Lines=1):
	static {

		echo ($Line ?? ''), str_repeat(PHP_EOL, $Lines);
		return $this;
	}

	////////////////////////////////////////////////////////////////
	////////////////////////////////////////////////////////////////

	public function
	Format(string $Fmt='', ?string $Preset=NULL, string|Common\Units\Colour $Colour=NULL, bool $Bold=FALSE, bool $Italic=FALSE, bool $Underline=FALSE):
	Common\Text {

		$Opts = NULL;

		if($Preset !== NULL) {
			$Opts = static::FmtPresets[$Preset];

			if(isset($Opts['Colour']) && is_string($Opts['Colour']))
			$Opts['Colour'] = new Common\Units\Colour($Opts['Colour']);
		}

		else {
			if(is_string($Colour))
			$Colour = new Common\Units\Colour($Colour);

			$Opts = [
				'Colour'    => $Colour,
				'Bold'      => $Bold,
				'Italic'    => $Italic,
				'Underline' => $Underline
			];
		}

		$Output = Common\Text::New(
			$Fmt,
			Common\Text::ModeTerminal,
			...$Opts
		);

		return $Output;
	}

	public function
	FormatLn(string $Fmt='', ?string $Preset=NULL, int $Lines=1, string|Common\Units\Colour $Colour=NULL, bool $Bold=FALSE, bool $Italic=FALSE, bool $Underline=FALSE):
	static {

		echo $this->Format(
			Fmt: $Fmt,
			Colour: $Colour,
			Bold: $Bold,
			Italic: $Italic,
			Underline: $Underline,
			Preset: $Preset
		);

		echo str_repeat(PHP_EOL, $Lines);

		return $this;
	}

	////////////////////////////////////////////////////////////////
	////////////////////////////////////////////////////////////////

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
	PromptBool(?string $Msg=NULL, ?string $Prompt=NULL, bool $Condition=TRUE, mixed $Input=STDIN):
	bool {

		$Result = Common\Datafilters::TypeBool($this->Prompt(
			$Msg,
			$Prompt,
			$Input
		));

		return ($Result === $Condition);
	}

	public function
	PromptTrue(?string $Msg=NULL, ?string $Prompt=NULL, mixed $Input=STDIN):
	bool {

		return $this->PromptBool($Msg, $Prompt, TRUE, $Input);
	}

	public function
	PromptFalse(?string $Msg=NULL, ?string $Prompt=NULL, mixed $Input=STDIN):
	bool {

		return $this->PromptBool($Msg, $Prompt, FALSE, $Input);
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

		if($this->AppInfo->AutoCmd)
		$Picked = $this->AppInfo->AutoCmd;

		if($Picked !== NULL)
		$Verbose = TRUE;

		if($Version) {
			$this->PrintLn($this->AppInfo->Version);
			return 0;
		}

		////////

		if(!$Picked) {
			$this
			->PrintLn(sprintf(
				'%s %s',
				$this->AppInfo->Name, $this->AppInfo->Version
			))
			->PrintLn($this->AppInfo->Desc)
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
			$Options = new Common\Datastore($Options);
			else
			$Options = new Common\Datastore;

			if($Args)
			$Args = new Common\Datastore($Args);

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

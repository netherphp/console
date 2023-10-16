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
	FmtDefault    = 'Default',
	FmtDefaultAlt = 'DefaultAlt',
	FmtPrime      = 'Primary',
	FmtPrimeAlt   = 'PrimaryAlt',
	FmtAccent     = 'Accent',
	FmtAccentAlt  = 'AccentAlt',
	FmtError      = 'Error',
	FmtErrorAlt   = 'ErrorAlt',
	FmtOK         = 'OK',
	FmtOKAlt      = 'OKAlt',
	FmtMuted      = 'Muted',
	FmtMutedAlt   = 'MutedAlt';

	const
	FmtPresets = [
		'Default'      => [],
		'DefaultAlt'   => [],
		'Primary'      => [ 'Bold'=> TRUE, 'Colour'=> '#F6684E' ],
		'PrimaryAlt'   => [ 'Bold'=> TRUE, 'Colour'=> '#FAA99A' ],
		'Accent'       => [ 'Bold'=> TRUE, 'Colour'=> '#E3C099' ],
		'AccentAlt'    => [ 'Bold'=> TRUE, 'Colour'=> '#EFDBC5' ],
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

	public Common\Datastore
	$ExtraData;

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
		$this->ExtraData = new Common\Datastore;

		$this->OnPrepare();
		$this->OnReady();

		return;
	}

	protected function
	OnPrepare():
	void {

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

		$Methods = static::GetMethodsWithAttribute(Meta\Command::class);
		$Commands = [];
		$M = NULL;
		$A = NULL;

		/** @var Common\Prototype\MethodInfo $M */
		/** @var Meta\Command $A */

		foreach($Methods as $M)
		foreach($M->GetAttributes(Meta\Command::class) as $A)
		$Commands[$A->Name] = $M;

		////////

		$this->Commands = Common\Datastore::FromArray($Commands);
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

	#[Common\Meta\Deprecated('2023-09-15')]
	public function
	FormatPrimary(string $Text):
	string {

		return $this->Formatter->{$this->ColourPrimary}($Text);
	}

	#[Common\Meta\Deprecated('2023-09-15')]
	public function
	FormatSecondary(string $Text):
	string {

		return $this->Formatter->{$this->ColourSecondary}($Text);
	}

	#[Common\Meta\Deprecated('2023-09-15')]
	public function
	FormatErrorPrimary(string $Text):
	string {

		return $this->Formatter->BoldRed($Text);
	}

	#[Common\Meta\Deprecated('2023-09-15')]
	public function
	FormatErrorSecondary(string $Text):
	string {

		return $this->Formatter->Red($Text);
	}

	#[Common\Meta\Deprecated('2023-09-15')]
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

	#[Common\Meta\Deprecated('2023-09-15')]
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

		return $Result ?? 0;
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

		// @todo 2023-09-15 this should be returning, not printing.

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

	public function
	FormatHeading(string $Text, string $Preset=self::FmtPrime):
	string {

		return $this->Format($Text, $Preset);
	}

	public function
	FormatBulletList(iterable $List, string $NamePreset=self::FmtAccent, string $DataPreset=NULL, string $Bull='•', string $BullPreset=NULL):
	string {

		$Output = '';
		$Name = NULL;
		$Data = NULL;

		$BullPreset ??= $NamePreset;

		////////

		foreach($List as $Name => $Data) {
			$Output .= sprintf(
				'%s %s %s%s',
				$this->Format($Bull, $BullPreset),
				$this->Format("{$Name}:", $NamePreset),
				$this->Format($Data, $DataPreset),
				PHP_EOL
			);
		}

		return $Output;
	}

	public function
	FormatTopicList(iterable $List, string $NamePreset=self::FmtAccent, string $DataPreset=NULL):
	string {

		$Output = '';
		$Name = NULL;
		$Data = NULL;

		$BullPreset ??= $NamePreset;

		////////

		foreach($List as $Name => $Data) {
			$Output .= sprintf(
				'%s%s%s%s%s',
				$this->Format($Name, $NamePreset), PHP_EOL,
				$this->Format($Data, $DataPreset), PHP_EOL,
				PHP_EOL
			);
		}

		$Output = sprintf(
			'%s%s',
			rtrim($Output),
			PHP_EOL
		);

		return $Output;
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

		$Result = Common\Filters\Numbers::BoolType($this->Prompt(
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

	#[Common\Meta\Date('2023-10-12')]
	#[Common\Meta\Info('Ask for text input from STDIN in a nice way.')]
	protected function
	PromptForValue(string $Name, ?string $Type=NULL, bool $Required=FALSE, ?callable $Filter=NULL, mixed $Default=NULL):
	mixed {

		$Result = NULL;

		while($Result === NULL) {
			$Result = Common\Filters\Text::TrimmedNullable($this->Prompt(
				$this->Format("{$Name}:", static::FmtAccent),
				$this->Format(
					sprintf(
						'%s%s:',
						$Type,
						($Default ? " ({$Default})" : "")
					),
					static::FmtMuted
				)
			));

			if($Result === NULL)
			$Result = $Default;

			////////

			if(is_callable($Filter))
			$Result = $Filter($Result, $Name, $Type);

			if($Result !== NULL)
			break;

			if($Required === FALSE)
			break;

			////////

			$this->FormatLn("{$Name} is Required.", static::FmtError);
			$this->FormatLn("(expects: {$Type})", static::FmtMuted, 2);
		}

		return $Result;
	}

	////////////////////////////////////////////////////////////////
	////////////////////////////////////////////////////////////////

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

	#[Meta\Command('help', TRUE)]
	#[Meta\Arg('command', 'Only show help for specific command.')]
	#[Meta\Toggle('--verbose', 'Shows all of the helpful.')]
	#[Meta\Info('Display this help.')]
	public function
	HandleCommandHelp():
	int {

		$Class = $this->GetClassInfo();
		$Picked = $this->GetInput(1);
		$Verbose = $this->GetOption('verbose') ?? FALSE;
		$Version = $this->GetOption('version') ?? FALSE;

		////////

		if($this->AppInfo->AutoCmd)
		$Picked = $this->AppInfo->AutoCmd;

		if($Picked !== NULL)
		$Verbose = TRUE;

		////////

		$this->PrintLn(sprintf(
			'%s %s',
			$this->FormatHeading($this->AppInfo->Name),
			$this->Format("// {$this->AppInfo->Version}", static::FmtMuted)
		), 2);

		if($Version)
		return 0;

		////////

		$this->PrintLn(sprintf(
			'%s %s <command> <args>',
			$this->Format('USAGE:', static::FmtAccent),
			$this->Name
		), 2);

		if(!$Picked)
		$this->PrintLn($this->FormatBulletList([
			'help <command>' => 'view help for specific command.',
			'help --verbose' => 'view all help for all commands.'
		]));

		////////

		($this->Commands)
		->Each(function(Common\Prototype\MethodInfo $Method) use($Class, $Picked, $Verbose) {

			$Commands = new Common\Datastore($Method->GetAttributes(Meta\Command::class));
			$Commands->Each(function(Meta\Command $Command) use($Class, $Method, $Picked, $Verbose) {

				if($Picked && $Picked !== $Command->Name)
				return;

				if($Command->Hide)
				return;

				////////

				$Title = $this->Format($Command->Name, static::FmtPrime);
				$Text = 'No info provided.';

				$Args = Common\Datastore::FromStackMerged(
					$Class->GetAttributes(Meta\Arg::class),
					$Method->GetAttributes(Meta\Arg::class)
				);

				$Opts = Common\Datastore::FromStackMerged(
					$Class->GetAttributes(Meta\Option::class),
					$Method->GetAttributes(Meta\Option::class),
					$Class->GetAttributes(Meta\Toggle::class),
					$Method->GetAttributes(Meta\Toggle::class),
					$Class->GetAttributes(Meta\Value::class),
					$Method->GetAttributes(Meta\Value::class)
				);

				$Infos = Common\Datastore::FromStackMerged(
					$Method->GetAttributes(Meta\Info::class)
				);

				////////

				if($Args->Count())
				$Title .= sprintf(' %s', (
					$Args
					->Map(fn(Meta\Arg $A)=> "<{$A->Name}>")
					->Join(' ')
				));

				if($Opts->Count())
				$Title .= sprintf(' %s', (
					$Opts
					->Map(function(Meta\Option $Opt){

						if($Opt instanceof Meta\Value)
						return "{$Opt->Name}=…";

						return $Opt->Name;
					})
					->Join(' ')
				));

				if($Infos->Count())
				$Text = $Infos->Map(fn(Meta\Info $I)=> $I->Text)->Join(' ');

				////////

				$this->PrintLn($Title);
				$this->PrintLn($this->Format($Text, static::FmtMuted), 2);

				if($Verbose && $Opts->Count()) {
					$Opts->Each(function(Meta\Option $Opt) {
						$Name = $Opt->Name;
						$Text = $this->Format($Opt->Text, static::FmtMuted);
						$Value = '';

						if($Opt instanceof Meta\Value)
						$Value = '=…';

						$this->PrintLn("\t{$Name}{$Value}");
						$this->PrintLn("\t{$Text}");
						$this->PrintLn();
						return;
					});

				}

				return;
			});

			return;
		});

		return 0;
	}

	////////////////////////////////////////////////////////////////
	////////////////////////////////////////////////////////////////

	static public function
	Realboot(array $Input):
	int {

		$Argv = Common\Datastore::FromArray($_SERVER['argv']);

		$Argv->MergeRight(array_map(
			fn($K, $V)=> "--{$K}={$V}",
			array_keys($Input),
			array_values($Input)
		));

		$App = new static($Argv->GetData());

		return $App->Run();
	}

}

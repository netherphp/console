<?php

namespace Nether\Console;

////////////////////////////////////////////////////////////////////////////////
////////////////////////////////////////////////////////////////////////////////

use Nether\Common;

use Phar;
use Throwable;
use Nether\Dye\Colour;

////////////////////////////////////////////////////////////////////////////////
////////////////////////////////////////////////////////////////////////////////

class Client {

	use
	Common\Package\ClassInfoPackage,
	Common\Package\MethodInfoPackage;

	////////////////////////////////////////////////////////////////
	////////////////////////////////////////////////////////////////

	const
	FmtDefault = 'Default',
	FmtPrime   = 'Primary',
	FmtAccent  = 'Accent',
	FmtError   = 'Error',
	FmtOK      = 'OK',
	FmtMuted   = 'Muted',
	FmtStrong  = 'Strong';

	////////////////////////////////////////////////////////////////
	////////////////////////////////////////////////////////////////

	public Meta\Application
	$AppInfo;

	public string
	$File;

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

	public Theme
	$Theme;

	public Common\Units\Vec2
	$Size;

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

		$this->File = realpath($_SERVER['SCRIPT_NAME']);
		$this->ExtraData = new Common\Datastore;
		$this->Formatter = new TerminalFormatter; // @deprecated 2023-07-26

		($this)
		->ReadAppInfo()
		->BuildCommandIndex()
		->ParseArguments($Argv);

		$this->ApplyDefaultSize();
		$this->ApplyDefaultTheme();
		$this->ApplyDefaultSort();

		$this->OnPrepare();
		$this->OnReady();

		return;
	}

	////////////////////////////////////////////////////////////////
	////////////////////////////////////////////////////////////////

	#[Common\Meta\Info('Runs from __Construct() once the object is confident about itself.')]
	protected function
	OnPrepare():
	void {

		return;
	}

	#[Common\Meta\Info('Runs from __Construct() after OnPrepare() as another pass.')]
	protected function
	OnReady():
	void {

		return;
	}

	#[Common\Meta\Info('Runs before the real method within the same exception handler.')]
	protected function
	OnRun():
	void {

		return;
	}

	////////////////////////////////////////////////////////////////
	////////////////////////////////////////////////////////////////

	#[Common\Meta\Date('2023-11-14')]
	#[Common\Meta\Info('Load and apply a theme. Override this method to change the theme selection logic.')]
	protected function
	ApplyDefaultTheme():
	void {

		if($this->IsUserAdmin()) {
			$this->Theme = new Themes\DefaultAdmin;
			$this->AppInfo->Name .= ' (Admin)';
		}

		else {
			$this->Theme = new Themes\DefaultUser;
		}

		return;
	}

	#[Common\Meta\Date('2023-11-14')]
	#[Common\Meta\Info('Detect and apply a default terminal size. Override this method to change the terminal size detection.')]
	protected function
	ApplyDefaultSize():
	void {

		$this->Size = static::FetchTerminalSize();
		//$this->Size->ClampX(0, 80);

		return;
	}

	#[Common\Meta\Date('2023-11-14')]
	#[Common\Meta\Info('Sort the command list for the help command. Override this method to apply custom sorting.')]
	protected function
	ApplyDefaultSort():
	void {

		$this->Commands->SortKeys();

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
		$Phar = Phar::Running(FALSE);

		// bring in the main application information from the application
		// attribute.

		if(!$AttrAppl)
		$AttrAppl = new Meta\Application(
			Name: static::AppName,
			Version: static::AppVersion,
			AutoCmd: NULL,
			Phar: $Phar
		);

		$this->AppInfo = $AttrAppl;

		// bring in the application description from the info attribute.

		if($AttrInfo)
		$this->AppInfo->Desc = $AttrInfo->Text;

		if($Phar)
		$this->AppInfo->Phar = $Phar;

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

	#[Common\Meta\Date('2023-11-02')]
	public function
	GetAppFile():
	string {

		return $this->File;
	}

	#[Common\Meta\Date('2023-11-02')]
	public function
	GetAppDir():
	string {

		$Chop = 2;

		if(Phar::Running() !== FALSE)
		$Chop = 1;

		return dirname(__FILE__, $Chop);
	}

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
				$this->Format("[{$this->AppInfo->Name}:UnhandledException]", Theme::Error),
				$Message
			))
			->PrintLn(sprintf(
				'%s %s:%s',
				$this->Format("[{$this->AppInfo->Name}:UnhandledException]", Theme::Muted),
				$Err->GetFile(),
				$Err->GetLine()
			))
			->PrintLn()
			->PrintLn($this->Format($Err->GetTraceAsString(), Theme::Muted));

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

		$EOL = match(TRUE) {
			($this->GetOption('cli-newline-html'))
			=> str_repeat(sprintf('%s<br />', PHP_EOL), $Lines),

			default
			=> str_repeat(PHP_EOL, $Lines)
		};

		echo ($Line ?? ''), $EOL;
		return $this;
	}

	////////////////////////////////////////////////////////////////
	// Manual Formatting Methods ///////////////////////////////////

	#[Common\Meta\Date('2023-11-03')]
	public function
	Format(string $Text='', ?string $Type=NULL, string|Colour $C=NULL, bool $Bd=FALSE, bool $It=FALSE, bool $Un=FALSE):
	Common\Text {

		$Argv = match(TRUE) {
			($Type !== NULL && $this->Theme->Has($Type))
			=> $Argv = $this->Theme->Get($Type),

			default
			=> [ 'Colour' => $C, 'Bold' => $Bd, 'Italic' => $It, 'Underline' => $Un ]
		};

		////////

		if(isset($Argv['Colour']) && is_string($Argv['Colour']))
		$Argv['Colour'] = new Colour($Argv['Colour']);

		////////

		if($this->GetOption('cli-format-html')) {
			return Common\Text::New($Text, Common\Text::ModeTagSpan, ...$Argv);
		}

		return Common\Text::New($Text, Common\Text::ModeTerminal, ...$Argv);
	}

	public function
	FormatLn(string $Fmt='', ?string $Type=NULL, int $Lines=1, string|Colour $C=NULL, bool $Bd=FALSE, bool $It=FALSE, bool $Un=FALSE):
	static {

		// @todo 2023-09-15 this should be returning, not printing.

		echo $this->Format(
			Text: $Fmt,
			Type: $Type,
			C: $C,
			Bd: $Bd,
			It: $It,
			Un: $Un
		);

		echo str_repeat(PHP_EOL, $Lines);

		return $this;
	}

	public function
	FormatHeading(string $Text, string $Preset=Theme::Prime):
	string {

		return $this->Format($Text, $Preset);
	}

	public function
	FormatHeaderBlock(string $Text, string $Preset=Theme::Prime, string $Char = '█'):
	string {

		/*
		██████████████████████████████████
		██ Example ███████████████████████
		*/

		$Label = sprintf('%s %s ', str_repeat($Char, 2), $Text);
		$Fill = max(0, ($this->Size->X - mb_strlen($Label)));

		$Line = $this->Format(str_repeat($Char, $this->Size->X), $Preset);
		$Line .= PHP_EOL;

		$Line .= $this->Format(
			sprintf('%s%s', $Label, str_repeat($Char, $Fill)),
			$Preset
		);

		return $Line;
	}

	public function
	FormatHeaderLine(string $Text, string $Preset=Theme::Prime, string $Char = '█'):
	string {

		/*
		██ Example ███████████████████████
		*/

		//$Size = static::FetchTerminalSize();
		$Label = sprintf('%s %s ', str_repeat($Char, 2), $Text);
		$Fill = max(0, ($this->Size->X - mb_strlen($Label)));

		$Line = $this->Format(
			sprintf('%s%s', $Label, str_repeat($Char, $Fill)),
			$Preset
		);

		return $Line;
	}

	public function
	FormatHeaderPoint(string $Text, string $Preset=Theme::Prime, string $Char = '█'):
	string {

		/*
		██ Example
		*/

		$Size = static::FetchTerminalSize();
		$Label = sprintf('%s %s ', str_repeat($Char, 2), $Text);

		$Line = $this->Format(
			$Label,
			$Preset
		);

		return $Line;
	}

	public function
	FormatBulletList(iterable $List, string $NamePreset=Theme::Accent, string $DataPreset=NULL, string $Bull='•', string $BullPreset=NULL):
	string {

		$Output = '';
		$Name = NULL;
		$Data = NULL;

		$List = Common\Datastore::FromArray($List);
		$IsNumeric = $List->IsList();

		$BullPreset ??= $NamePreset;

		////////

		foreach($List as $Name => $Data) {
			if($IsNumeric)
			$Name = (((int)$Name) + 1);

			$Output .= sprintf(
				'%s %s %s%s',
				$this->Format($Bull, $BullPreset),
				$this->Format("{$Name}:", $NamePreset),
				$this->Format($Data, $DataPreset),
				PHP_EOL
			);
		}

		return trim($Output);
	}

	public function
	FormatTopicList(iterable $List, string $NamePreset=Theme::Accent, string $DataPreset=NULL):
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

		return trim($Output);
	}

	public function
	FormatWrap(string $Input, ?int $Width=NULL):
	string {

		$Width ??= $this->Size->X;

		return wordwrap($Input, $Width);
	}

	////////////////////////////////////////////////////////////////
	// Contextually Prepared Formatting Methods ////////////////////

	// These methods are designed intended to be PrintLn'd and they will
	// include whatever extra lines were determined were needed to make the
	// elements look good.

	#[Common\Meta\Date('2023-11-14')]
	#[Common\Meta\Info('Returns theme-styled content suitable for an H1 division with an extra line break after.')]
	public function
	FormatH1(string $Text):
	string {

		return sprintf(
			'%s%s',
			$this->FormatHeaderLine($Text, Theme::Prime),
			PHP_EOL
		);
	}

	#[Common\Meta\Date('2023-11-14')]
	#[Common\Meta\Info('Returns theme-styled content suitable for an H2 division with an extra line break after.')]
	public function
	FormatH2(string $Text):
	string {

		return sprintf(
			'%s%s',
			$this->FormatHeaderLine($Text, Theme::Accent),
			PHP_EOL
		);
	}

	#[Common\Meta\Date('2023-11-14')]
	#[Common\Meta\Info('Returns theme-styled content suitable for an H3 division with an extra line break after.')]
	public function
	FormatH3(string $Text):
	string {

		return sprintf(
			'%s%s',
			$this->FormatHeaderPoint($Text, Theme::Accent),
			PHP_EOL
		);
	}

	#[Common\Meta\Date('2023-11-14')]
	#[Common\Meta\Info('Returns theme-styled content suitable for an H4 division with an extra line break after.')]
	public function
	FormatH4(string $Text):
	string {

		return sprintf(
			'%s%s',
			$this->Format($Text, Theme::Accent, Bd: TRUE),
			PHP_EOL
		);
	}

	#[Common\Meta\Date('2024-04-22')]
	public function
	FormatTable(array $Head, array $Data, ?array $Fmts=NULL, ?array $Styles=NULL):
	string {

		$Delim = ' | ';
		$TWidth = $this->Size->X;
		$Output = '';
		$ColMax = [];
		$LineMax = 0;
		$Row = NULL;
		$Joiner = NULL;
		$CR = NULL;
		$CK = NULL;
		$CV = NULL;
		$LastKey = NULL;

		// make sure the datasets make sense.

		if(!count($Head))
		throw new Common\Error\RequiredDataMissing('Table Headers', 'array of strings');

		if(count($Data) && count($Data[0]) !== count($Head))
		throw new Common\Error\FormatInvalid('header/row column count mismatch');

		if($Fmts === NULL)
		$Fmts = array_fill(0, count($Head), 's');

		if($Styles === NULL)
		$Styles = array_fill(0, count($Data), $this->Theme::Default);

		if(count($Fmts) && (count($Fmts) !== count($Head)))
		throw new Common\Error\FormatInvalid('header/formats column count mismatch');

		if(count($Styles) && (count($Styles) !== count($Data)))
		throw new Common\Error\FormatInvalid('data/styles row count mismatch');

		// find the max width of each column in this dataset.

		foreach($Head as $CK=> $CV)
		$ColMax[$CK] = strlen($CV);

		foreach($Data as $Row) {
			foreach($Row as $CK=> $CV) {
				// @TODO 2024-04-22 handle callable() fmt
				$CV = sprintf("%{$Fmts[$CK]}", $CV);

				if(strlen($CV) > $ColMax[$CK])
				$ColMax[$CK] = strlen($CV);
			}
		}

		// determine the longest line we will try to print.

		$LineMax = array_sum($ColMax);
		$LineMax += (strlen($Delim) * count($ColMax)) - strlen($Delim);

		// allow the final column to flood the terminal width.

		$LastKey = array_reverse(array_keys($Head))[0];
		$ColMax[$LastKey] = $TWidth - ($LineMax - $ColMax[$LastKey]);

		////////

		$Joiner = [];

		foreach($Head as $CK=> $CV)
		$Joiner[] = $this->Format(
			sprintf("%-{$ColMax[$CK]}s", substr($CV, 0, $ColMax[$CK])),
			Theme::Prime
		);

		$Output .= join($this->Format(' | ', Theme::Muted), $Joiner);
		$Output .= PHP_EOL;
		$Output .= $this->Format(str_repeat('=', $LineMax), Theme::Accent);
		$Output .= PHP_EOL;

		////////

		foreach($Data as $CR=> $Row) {
			$Joiner = [];

			foreach($Row as $CK=> $CV) {
				$CV = sprintf("%{$Fmts[$CK]}", $CV);

				$Joiner[] = $this->Format(
					sprintf(
						"%-{$ColMax[$CK]}s",
						substr($CV, 0, $ColMax[$CK])
					),
					$Styles[$CR]
				);
			}

			$Output .= join($this->Format(' | ', Theme::Muted), $Joiner);
			$Output .= PHP_EOL;
		}

		////////

		return $Output;
	}

	#[Common\Meta\Date('2023-11-16')]
	protected function
	PrintH1(string $Text):
	static {

		$this->PrintLn($this->FormatH1($Text));

		return $this;
	}

	#[Common\Meta\Date('2023-11-16')]
	protected function
	PrintH2(string $Text):
	static {

		$this->PrintLn($this->FormatH2($Text));

		return $this;
	}

	#[Common\Meta\Date('2023-11-16')]
	protected function
	PrintH3(string $Text):
	static {

		$this->PrintLn($this->FormatH3($Text));

		return $this;
	}

	#[Common\Meta\Date('2023-11-16')]
	protected function
	PrintH4(string $Text):
	static {

		$this->PrintLn($this->FormatH4($Text));

		return $this;
	}

	#[Common\Meta\Date('2024-04-22')]
	protected function
	PrintTable(array $Head, array $Data, ?array $Fmts=NULL, ?array $Styles=NULL):
	static {

		$this->PrintLn($this->FormatTable(
			$Head, $Data, $Fmts, $Styles
		));

		return $this;
	}

	#[Common\Meta\Date('2023-11-16')]
	protected function
	PrintAppHeader(?string $Title=NULL):
	static {

		if($this->GetOption('cli-no-appheader'))
		return $this;

		////////

		$Label = $this->AppInfo->Name;

		if($Title !== NULL)
		$Label .= ": {$Title}";

		$this->PrintLn($this->FormatH1($Label));

		return $this;
	}

	#[Common\Meta\Date('2023-11-16')]
	public function
	PrintBulletList(iterable $List):
	static {

		$this->PrintLn($this->FormatBulletList($List), 2);

		return $this;
	}

	#[Common\Meta\Date('2023-11-16')]
	protected function
	PrintOK(?string $More=NULL, ?string $Yell='OK'):
	static {

		$Msg = match(TRUE) {
			($More !== NULL)
			=> "{$Yell}: {$More}",

			default
			=> $Yell
		};

		$this->PrintLn($this->FormatHeaderPoint(
			$Msg, Theme::OK
		), 2);

		return $this;
	}

	#[Common\Meta\Date('2023-11-16')]
	protected function
	PrintError(?string $More=NULL, ?string $Yell='ERROR'):
	static {

		$Msg = match(TRUE) {
			($More !== NULL)
			=> "{$Yell}: {$More}",

			default
			=> $Yell
		};

		$this->PrintLn($this->FormatHeaderPoint(
			$Msg, Theme::Error
		), 2);

		return $this;
	}

	#[Common\Meta\Date('2023-11-16')]
	protected function
	PrintStatus(string $Msg):
	static {

		$this->PrintLn($this->FormatHeaderPoint(
			$Msg, Theme::Default
		), 2);

		return $this;
	}

	#[Common\Meta\Date('2023-11-16')]
	protected function
	PrintStatusMuted(string $Msg):
	static {

		$this->PrintLn($this->FormatHeaderPoint(
			$Msg, Theme::Muted
		), 2);

		return $this;
	}

	#[Common\Meta\Date('2023-11-16')]
	protected function
	PrintStatusAlert(string $Msg):
	static {

		$this->PrintLn($this->FormatHeaderPoint(
			$Msg, Theme::Alert
		), 2);

		return $this;
	}

	#[Common\Meta\Date('2023-11-16')]
	protected function
	PrintStatusWarning(string $Msg):
	static {

		$this->PrintLn($this->FormatHeaderPoint(
			$Msg, Theme::Warning
		), 2);

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

		$IsAdmin = $this->IsUserAdmin();
		$SudoPath = trim(`which sudo`);

		if($IsAdmin)
		return FALSE;

		return pcntl_exec(
			$SudoPath,
			$this->Args->Source
		);
	}

	#[Common\Meta\Date('2023-11-11')]
	public function
	IsUserAdmin():
	bool {

		if(PHP_OS_FAMILY !== 'Windows')
		return (posix_getuid() === 0);

		return FALSE;
	}

	public function
	IsPharing():
	bool {

		return Phar::Running(FALSE) !== '';
	}

	////////////////////////////////////////////////////////////////
	// COMMAND: help ///////////////////////////////////////////////

	#[Meta\Command('help', TRUE)]
	#[Meta\Info('Display this help.')]
	#[Meta\Arg('command', 'Only show help for specific command.')]
	#[Meta\Toggle('--verbose', 'Shows all of the helpful.')]
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

		if(!$this->GetOption('cli-no-appheader')) {
			$this->PrintLn($this->FormatHeaderLine(
				$this->AppInfo->Name, Theme::Prime
			));

			$this->PrintLn($this->FormatHeaderLine(
				"Version: {$this->AppInfo->Version}",
				Theme::Muted
			));

			$this->PrintLn();
		}

		if($Version)
		return 0;

		////////

		$this->PrintLn(sprintf(
			'%s %s%s <command> <args>',
			$this->Format('USAGE:', Theme::Accent),
			basename($this->Name),
			($Picked)?(" {$Picked}"):('')
		), 2);

		if(!$Picked)
		$this->PrintLn($this->FormatBulletList([
			'help <command>' => 'view help for specific command.',
			'help --verbose' => 'view all help for all commands.'
		]), 2);

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
				$this->PrintLn($this->Format($Text, Theme::Muted), 2);

				if($Verbose && $Opts->Count()) {
					$Opts->Each(function(Meta\Option $Opt) {
						$Name = $Opt->Name;
						$Text = $this->Format($Opt->Text, Theme::Muted);
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
	// COMMAND: phar ///////////////////////////////////////////////

	#[Meta\Command('phar', TRUE)]
	#[Meta\Info('Compile a PHAR for easy use/distribution.')]
	#[Meta\Option('ver', TRUE, 'Version to append to the PHAR. Use "null" to skip versioning.')]
	#[Meta\Error(1, 'Phar creation must be enabled in php.ini via phar.readonly=0')]
	#[Meta\Error(2, 'Phar creation must be enabled by setting AppInfo.Phar to the desired filename.')]
	public function
	HandleCommandPhar():
	int {

		if(ini_get('phar.readonly'))
		$this->Quit(1);

		if(!$this->AppInfo->Phar)
		$this->Quit(2);

		////////

		$Outfile = $this->GetPharOut();
		$Bin = $this->GetPharBin();
		$Version = $this->GetOption('ver') ?? TRUE;
		$Files = $this->GetPharFiles();
		$FileFilters = $this->GetPharFileFilters();
		$BaseDir = $this->GetPharBaseDir();

		////////

		if($Version === TRUE)
		$Version = $this->GetPharVersion();

		if($Version === '' || $Version === 'null')
		$Version = '';

		////////

		$Phar = Common\Phar\Builder::From(
			PharOut: $Outfile,
			Bin: $Bin,
			Version: $Version,
			Files: $Files,
			FileFilters: $FileFilters,
			BaseDir: $BaseDir
		);

		$Phar->Build($Version);

		return 0;
	}

	#[Common\Meta\Date('2023-10-31')]
	#[Common\Meta\Info('Return the project base dir.')]
	protected function
	GetPharBaseDir():
	string {

		// this default implementation assumes that executable scripts
		// are kept in a subdir like `bin` or `scripts` such that the
		// project root is two steps up from here.

		return dirname($this->File, 2);
	}

	#[Common\Meta\Date('2023-10-31')]
	#[Common\Meta\Info('Return the name of the entry point bin script.')]
	protected function
	GetPharBin():
	string {

		// return a project relative path to the file that should behave
		// as the cli command entry point.

		return Common\Filesystem\Util::Prechomp(
			$this->GetPharBaseDir(), $this->File
		);
	}

	#[Common\Meta\Date('2023-10-31')]
	#[Common\Meta\Info('Return the final phar filename.')]
	protected function
	GetPharOut():
	string {

		// typically a whatever.phar in the root of the build.
		// default implementation tries to name it the same as the default
		// bin file but with a phar extension right in the build root.

		return $this->AppInfo->Phar;
	}

	#[Common\Meta\Date('2024-09-26')]
	#[Common\Meta\Info('Return the final phar filename.')]
	protected function
	GetPharVersion():
	string {

		// typically a whatever.phar in the root of the build.
		// default implementation tries to name it the same as the default
		// bin file but with a phar extension right in the build root.

		return $this->AppInfo->Version;
	}

	#[Common\Meta\Date('2023-10-31')]
	#[Common\Meta\Info('Return a list of files to bake.')]
	protected function
	GetPharFiles():
	Common\Datastore {

		// this should be overloaded to return an accurate list of files
		// to be included in the archive.

		$Index = new Common\Datastore([
			Common\Filesystem\Util::Prechomp($this->GetPharBaseDir(), $this->File),
			'composer.json',
			'composer.lock',
			'vendor'
		]);

		return $Index;
	}

	#[Common\Meta\Date('2023-10-31')]
	#[Common\Meta\Info('Return a list of filters to trim the final list of files to bake.')]
	protected function
	GetPharFileFilters():
	Common\Datastore {

		$Output = new Common\Datastore;

		return $Output;
	}

	////////////////////////////////////////////////////////////////
	// FACTORY API /////////////////////////////////////////////////

	static public function
	Realboot(array $Input=[]):
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

	static public function
	FetchTerminalSize():
	Common\Units\Vec2 {

		$Vec = match(PHP_OS_FAMILY) {
			'Linux'
			=> new Common\Units\Vec2(
				(int)`tput cols -T dumb`,
				(int)`tput lines -T dumb`
			),

			'Darwin'
			=> new Common\Units\Vec2(
				(int)`tput cols`,
				(int)`tput lines`
			),

			default
			=> new Common\Units\Vec2(80, 24)
		};

		//Common\Dump::Var(PHP_OS_FAMILY);
		//Common\Dump::Var($Vec);

		return $Vec;
	}

}

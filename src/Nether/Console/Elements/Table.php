<?php ##########################################################################
################################################################################

namespace Nether\Console\Elements;

use Nether\Common;
use Nether\Console;
use Nether\Dye;

################################################################################
################################################################################

class Table
extends Common\Prototype {

	public Console\Client
	$Client;

	#[Common\Meta\PropertyObjectify]
	public Common\Datastore
	$Widths;

	#[Common\Meta\PropertyObjectify]
	public Common\Datastore
	$Chars;

	#[Common\Meta\PropertyObjectify]
	public Common\Datastore
	$Headers;

	#[Common\Meta\PropertyObjectify]
	public Common\Datastore
	$Rows;

	#[Common\Meta\PropertyObjectify]
	public Common\Datastore
	$Show;

	public string
	$BorderCharH = '═';

	public string
	$BorderCharV = '║';

	public ?Dye\Colour
	$BorderColour = NULL;

	public ?Dye\Colour
	$HeaderColour = NULL;

	public ?Dye\Colour
	$TextColour = NULL;

	////////////////////////////////////////////////////////////////
	////////////////////////////////////////////////////////////////

	public function
	FetchTerminalWidth():
	int {

		return $this->Client->Size->X;
	}

	////////////////////////////////////////////////////////////////
	////////////////////////////////////////////////////////////////

	public function
	SetHeaders(...$Argv):
	static {

		$this->Headers->Clear();
		$this->Headers->MergeRight($Argv);

		$this->ShowAllColumns();

		return $this;
	}

	public function
	SetData(iterable $Rows):
	static {

		$this->Rows->Import($Rows);

		return $this;
	}

	public function
	Push(...$Args):
	static {

		$this->Rows->Push($Args);

		return $this;
	}

	////////////////////////////////////////////////////////////////
	////////////////////////////////////////////////////////////////

	public function
	HideColumn(int $Num):
	static {

		return $this->ShowColumn($Num, FALSE);
	}

	public function
	HideAllColumns():
	static {

		$this->Show->Import(array_fill(
			0, $this->Headers->Count(), FALSE
		));

		return $this;
	}

	public function
	ShowColumn(int $Num, bool $Value=TRUE):
	static {

		$this->Show[$Num] = $Value;

		return $this;
	}

	public function
	ShowAllColumns():
	static {

		$this->Show->Import(array_fill(
			0, $this->Headers->Count(), TRUE
		));

		return $this;
	}

	////////////////////////////////////////////////////////////////
	////////////////////////////////////////////////////////////////

	public function
	PrintHeaders():
	static {

		// a lot of the weird math in here is to put up with terminal
		// escape codes being zero width to us but not to string counting
		// things.

		$TW = $this->FetchTerminalWidth();
		$BH = $this->BorderCharH;
		$BV = $this->BorderCharV;
		$PC = ' ';

		$Key = NULL;
		$Name = NULL;
		$PLen = NULL;

		$Line = '';
		$LLen = 0;
		$Sane = '';
		$SLen = 0;
		$Diff = NULL;

		$LineBorder = NULL;
		$LineChop = NULL;
		$LinePad = NULL;
		$LineEnd = NULL;

		////////

		if($this->Widths->Count() === 0)
		$this->AnalyseWidths();

		////////

		foreach($this->Headers as $Key => $Name) {
			if(!$this->Show[$Key])
			continue;

			$PLen = max(0, ($this->Widths[$Key] - (mb_strlen($Name))));

			$Line .= sprintf(
				'%s %s%s ',
				$this->Client->Format($BV, C: $this->BorderColour),
				$Name,
				str_repeat($PC, $PLen)
			);
		}

		////////

		$Line = rtrim($Line);
		$LLen = mb_strlen($Line);
		$Sane = $this->StripTerminalCodes($Line);
		$SLen = mb_strlen($Sane);
		$Diff = $LLen - $SLen;

		$LineBorder = $this->Client->Format(str_repeat($BH, $TW), C: $this->BorderColour);
		$LineChop = mb_substr($Line, 0, (($TW-2) + $Diff));
		$LinePad = str_repeat($PC, max(0, ($TW - $SLen - 2)));
		$LineEnd = $this->Client->Format(sprintf(' %s', $BV), C: $this->BorderColour);

		////////

		echo $LineBorder, PHP_EOL;
		echo $LineChop, $LinePad, $LineEnd, PHP_EOL;
		echo $LineBorder, PHP_EOL;

		////////

		return $this;
	}

	public function
	PrintFooter(int $Newlines=1):
	static {

		$BChar = $this->BorderCharH;
		$TW = $this->FetchTerminalWidth();

		echo $this->Client->Format(str_repeat($BChar, $TW), C: $this->BorderColour), str_repeat(PHP_EOL, $Newlines);

		return $this;
	}

	public function
	PrintRows():
	static {

		$Min = 1;
		$TW = $this->FetchTerminalWidth();
		$BC = $this->BorderCharV;

		$Loop = NULL;
		$Row = NULL;
		$Key = NULL;
		$Value = NULL;
		$Format = NULL;
		$Line = NULL;
		$Len = NULL;

		foreach($this->Rows as $Loop => $Row) {
			/** @var array $Row */

			$Line = '';

			foreach($Row as $Key => $Value) {
				if(!$this->Show[$Key])
				continue;

				$Len = mb_strlen($this->StripTerminalCodes($Value));

				$Format = sprintf(
					'%s %s%s ',
					$this->Client->Format($BC, C: $this->BorderColour),
					$Value,
					str_repeat(' ', max(0, ($this->Widths[$Key] - $Len)) )
				);

				$Line .= sprintf($Format, $Value);
			}

			$Stripped = $this->StripTerminalCodes($Line);
			$SLen = mb_strlen($Stripped);

			$Len = mb_strlen($Line);
			$Diff = max(0, ($Len - $SLen));
			$End = max(0, ($TW - $SLen - 2));

			echo mb_substr($Line, 0, ($TW+$Diff) -2);
			echo str_repeat(' ', $End), $this->Client->Format(" {$BC}", C: $this->BorderColour), PHP_EOL;
		}

		return $this;
	}

	public function
	Print(int $Newlines=1):
	static {

		$this->PrintHeaders();
		$this->PrintRows();
		$this->PrintFooter($Newlines);

		return $this;
	}

	////////////////////////////////////////////////////////////////
	////////////////////////////////////////////////////////////////

	public function
	AnalyseWidths():
	static {

		$Label = NULL;
		$Row = NULL;
		$Field = NULL;

		////////

		$this->Widths->Clear();
		$this->Chars->Clear();

		// start with the headers.

		foreach($this->Headers as $Label) {
			$this->Widths->Push(mb_strlen($this->StripTerminalCodes($Label)));
			$this->Chars->Push(strlen($this->StripTerminalCodes($Label)));
		}

		// analyse the data.

		foreach($this->Rows as $Row)
		foreach($Row as $Field => $Label) {
			$Label ??= '';
			$Len = mb_strlen($this->StripTerminalCodes($Label));
			$Chr = strlen($this->StripTerminalCodes($Label));

			if($Len > $this->Widths[$Field]) {
				$this->Widths[$Field] = $Len;
				$this->Chars[$Field] = $Chr;
			}

			continue;
		}

		return $this;
	}

	public function
	StripTerminalCodes(string $Input):
	string {

		return preg_replace('#\e\[[0-9;]*m(?:\e\[K)?#', '', $Input);
	}

	////////////////////////////////////////////////////////////////
	////////////////////////////////////////////////////////////////

	static public function
	New(
		Console\Client $Client,
		?iterable $Headers=NULL,
		?iterable $Rows=NULL,
		?Dye\Colour $BorderColour=NULL,
		?Dye\Colour $HeaderColour=NULL,
		?Dye\Colour $TextColour=NULL,
		int $Print = 0
	):
	static {

		$Output = new static([
			'Client'       => $Client,
			'BorderColour' => $BorderColour,
			'HeaderColour' => $HeaderColour,
			'TextColour'   => $TextColour
		]);

		if($Headers)
		$Output->SetHeaders(...$Headers);

		if($Rows)
		$Output->SetData($Rows);

		////////

		if($Print > 0)
		$Output->Print($Print);

		////////

		return $Output;
	}

};

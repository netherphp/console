<?php ##########################################################################
################################################################################

namespace Nether\Console\Elements;

use Nether\Common;
use Nether\Console;

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
	SetData(array $Rows):
	static {

		$this->Rows->SetData($Rows);

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

		$Min = 1;
		$BChar = '=';
		$TW = $this->FetchTerminalWidth();

		$Key = NULL;
		$Name = NULL;
		$Format = NULL;
		$Line = '';

		////////

		if($this->Widths->Count() === 0)
		$this->AnalyseWidths();

		////////

		foreach($this->Headers as $Key => $Name) {
			if(!$this->Show[$Key])
			continue;

			$Format = sprintf('| %%- %ds ', max($Min, $this->Widths[$Key]));
			$Line .= sprintf($Format, $Name);
		}

		echo str_repeat($BChar, $TW), PHP_EOL;
		echo substr($Line, 0, $TW), PHP_EOL;
		echo str_repeat($BChar, $TW), PHP_EOL;

		////////

		return $this;
	}

	public function
	PrintFooter():
	static {

		$BChar = '=';
		$TW = $this->FetchTerminalWidth();

		echo str_repeat($BChar, $TW), PHP_EOL;

		return $this;
	}

	public function
	PrintRows():
	static {

		$Min = 1;
		$TW = $this->FetchTerminalWidth();

		$Loop = NULL;
		$Row = NULL;
		$Key = NULL;
		$Value = NULL;
		$Format = NULL;
		$Line = NULL;

		foreach($this->Rows as $Loop => $Row) {
			/** @var array $Row */

			$Line = '';

			foreach($Row as $Key => $Value) {
				if(!$this->Show[$Key])
				continue;

				$Format = sprintf('| %%- %ds ', max($Min, $this->Widths[$Key]));
				//s$Format = '| %s ';
				$Line .= sprintf($Format, $Value);
			}

			$Diff = mb_strlen($Line) - mb_strlen($this->StripTerminalCodes($Line));

			echo mb_substr($Line, 0, ($TW+$Diff)), PHP_EOL;
		}

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

		// start with the headers.

		foreach($this->Headers as $Label) {
			$this->Widths->Push(mb_strlen($this->StripTerminalCodes($Label)));
			$this->Chars->Push(mb_strlen($Label));
		}

		// analyse the data.

		foreach($this->Rows as $Row)
		foreach($Row as $Field => $Label) {
			$Label ??= '';
			$Len = mb_strlen($this->StripTerminalCodes($Label));

			if($Len > $this->Widths[$Field]) {
				$this->Widths[$Field] = $Len;
				$this->Chars->Push(mb_strlen($Label));
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
	New(Console\Client $Client):
	static {

		$Output = new static([
			'Client' => $Client
		]);

		return $Output;
	}

};

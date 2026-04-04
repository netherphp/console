<?php ##########################################################################
################################################################################

namespace Nether\Console\Elements;

use Nether\Common;
use Nether\Console;
use Nether\Dye;

################################################################################
################################################################################

class Text
extends Common\Prototype {

	public Console\Client
	$Client;

	public ?Dye\Colour
	$Colour = NULL;

	public string
	$Text = '';

	////////////////////////////////////////////////////////////////
	////////////////////////////////////////////////////////////////

	public function
	__ToString():
	string {

		return $this->Get();
	}

	////////////////////////////////////////////////////////////////
	////////////////////////////////////////////////////////////////

	public function
	Get(int $Newlines=0):
	string {

		$Output = $this->Client->Format($this->Text, C: $this->Colour);

		if($Newlines > 0)
		$Output .= str_repeat(PHP_EOL, $Newlines);

		return $Output;
	}

	public function
	Print(int $Newlines=0):
	void {

		$this->Client->PrintLn($this->Text, $Newlines);

		return;
	}

	////////////////////////////////////////////////////////////////
	////////////////////////////////////////////////////////////////

	static public function
	New(Console\Client $Client, string $Text='', ?Dye\Colour $Colour=NULL, int $Print=0):
	static {

		$Output = new static([
			'Client' => $Client,
			'Text'   => $Text
		]);

		if($Print > 0)
		$Output->Print($Print);

		return $Output;
	}

};
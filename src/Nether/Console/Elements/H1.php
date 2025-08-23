<?php ##########################################################################
################################################################################

namespace Nether\Console\Elements;

use Nether\Common;
use Nether\Console;
use Nether\Dye;

################################################################################
################################################################################

class H1
extends Common\Prototype {

	public Console\Client
	$Client;

	public string
	$Text = '';

	public string
	$BorderCharH = '█';

	public string
	$BorderCharV = '█';

	public ?Dye\Colour
	$BorderColour = NULL;

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
	Print(int $Newlines=1):
	void {

		$CharMax = $this->FetchTerminalWidth();
		$LinePrefix = sprintf('%s%s', $this->BorderCharV, $this->BorderCharH);
		$LineText = sprintf(' %s ', $this->Text);
		$LineSuffix = sprintf('%s%s', str_repeat($this->BorderCharH, ($CharMax - mb_strlen($LinePrefix.$LineText) - 1)), $this->BorderCharV);

		$Line = sprintf(
			'%s%s%s',
			$this->Client->Format($LinePrefix, C: $this->BorderColour),
			$this->Client->Format($LineText, C: $this->TextColour),
			$this->Client->Format($LineSuffix, C: $this->BorderColour)
		);

		$this->Client->PrintLn($Line, $Newlines);

		return;
	}

	////////////////////////////////////////////////////////////////
	////////////////////////////////////////////////////////////////

	static public function
	New(Console\Client $Client, ?string $Text=NULL, ?Dye\Colour $BorderColour=NULL, ?Dye\Colour $TextColour=NULL):
	static {

		$Output = new static([
			'Client'       => $Client,
			'Text'         => $Text ?? '',
			'BorderColour' => $BorderColour,
			'TextColour'   => $TextColour
		]);

		return $Output;
	}

};
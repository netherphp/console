<?php ##########################################################################
################################################################################

namespace Nether\Console\Elements;

use Nether\Common;
use Nether\Console;
use Nether\Dye;

################################################################################
################################################################################

class ListBullet
extends Common\Prototype {

	public Console\Client
	$Client;

	public Common\Datastore
	$Items;

	public string
	$DelimChar;

	public string
	$BulletChar;

	public ?Dye\Colour
	$BulletColour = NULL;

	public ?Dye\Colour
	$TextColour = NULL;

	public string
	$IndentChar;

	public int
	$IndentCount = 0;

	////////////////////////////////////////////////////////////////
	////////////////////////////////////////////////////////////////

	public function
	Print(int $Newlines=1):
	void {

		$Line = NULL;
		$Item = NULL;
		$Prefix = NULL;
		$Text = NULL;

		////////

		foreach($this->Items as $Item) {
			$Prefix = sprintf('%s%s', str_repeat($this->IndentChar, $this->IndentCount), $this->BulletChar);
			$Text = sprintf(' %s', $Item);

			$Line = sprintf(
				'%s%s',
				$this->Client->Format($Prefix, C: $this->BulletColour),
				$this->Client->Format($Text, C: $this->TextColour)
			);

			$this->Client->PrintLn($Line);
		}

		$this->Client->PrintLn('', $Newlines);

		return;
	}

	////////////////////////////////////////////////////////////////
	////////////////////////////////////////////////////////////////

	static public function
	New(
		Console\Client $Client,
		array          $Items = [],
		string         $BulletChar = "*",
		?Dye\Colour    $BulletColour = NULL,
		string         $DelimChar = ':',
		string         $IndentChar = "\t",
		int            $IndentCount = 0,
		?Dye\Colour    $TextColour = NULL
	):
	static {

		$Output = new static([
			'Client'       => $Client,
			'Items'        => Common\Datastore::FromArray($Items),
			'DelimChar'    => $DelimChar,
			'BulletChar'   => $BulletChar,
			'BulletColour' => $BulletColour,
			'IndentChar'   => $IndentChar,
			'IndentCount'  => $IndentCount,
			'TextColour'   => $TextColour
		]);

		return $Output;
	}

};
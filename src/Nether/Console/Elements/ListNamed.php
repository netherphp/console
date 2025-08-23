<?php ##########################################################################
################################################################################

namespace Nether\Console\Elements;

use Nether\Common;
use Nether\Console;
use Nether\Dye;

################################################################################
################################################################################

class ListNamed
extends ListBullet {

	public function
	Print(int $Newlines=1):
	void {

		$Line = NULL;
		$Name = NULL;
		$Item = NULL;
		$Prefix = NULL;
		$Name = NULL;

		////////

		foreach($this->Items as $Name => $Item) {

			$Prefix = sprintf(
				'%s%s%s ',
				str_repeat($this->IndentChar, $this->IndentCount),
				$Name,
				$this->DelimChar
			);

			$Line = sprintf(
				'%s%s',
				$this->Client->Format($Prefix, C: $this->BulletColour),
				$this->Client->Format($Item, C: $this->TextColour)
			);

			$this->Client->PrintLn($Line);
		}

		$this->Client->PrintLn('', $Newlines);

		return;
	}

};
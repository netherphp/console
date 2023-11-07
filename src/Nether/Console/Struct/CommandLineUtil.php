<?php

namespace Nether\Console\Struct;

use Nether\Console;

class CommandLineUtil {

	public string
	$Command;

	public array
	$Output;

	public int
	$Error;

	public function
	__Construct(string $Command) {

		$this->Command = $Command;
		$this->Reset();

		return;
	}

	public function
	Reset():
	static {

		$this->Output = [];
		$this->Error = 0;

		return $this;
	}

	public function
	Run():
	int {

		$this->Reset();

		exec($this->Command, $this->Output, $this->Error);

		return $this->Error;
	}

	public function
	Print(string $Prefix=''):
	static {


		echo $Prefix;
		echo join(sprintf('%s%s', PHP_EOL, $Prefix), $this->Output);
		echo PHP_EOL;

		return $this;
	}

	public function
	GetOutputString($Prefix=''):
	string {

		return join(sprintf('%s%s', PHP_EOL, $Prefix), $this->Output);
	}

	////////

	public function
	HasError():
	bool {

		$Error = $this->Error;

		////////

		// git push having nothing to push is not an error.

		if($Error)
		$Error = Console\CommandLibrary::DidItReallyFailTho($this);

		////////

		return ($Error !== 0);
	}

	public function
	HasOutput():
	bool {

		return (count($this->Output) > 0);
	}

}

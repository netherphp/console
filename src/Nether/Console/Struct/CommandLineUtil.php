<?php

namespace Nether\Console\Struct;

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
		$this->Error = 1;

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

}

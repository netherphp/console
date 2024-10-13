<?php ##########################################################################
################################################################################

namespace Nether\Console;

################################################################################
################################################################################

class ProcessRunner {

	public mixed
	$Proc;

	public string
	$Line;

	public array
	$Pipe = [];

	public bool
	$Verbose;

	public function
	__Construct($Line, bool $Verbose=FALSE) {

		$this->Line = $Line;
		$this->Verbose = $Verbose;

		return;
	}

	public function
	Run():
	static {

		$this->Proc = proc_open(
			$this->Line,
			[ ['pipe', 'r'], ['pipe', 'w'], ['pipe', 'w'] ],
			$this->Pipe
		);

		return $this;
	}

	public function
	IsOK():
	bool {

		return is_resource($this->Proc);
	}

	public function
	Spin():
	bool {

		$Line = NULL;

		while($this->IsOK())
		while($Line = fgets($this->Pipe[1])) {

			if($this->Verbose)
			echo $Line;

			continue;
		}

		return FALSE;
	}

};

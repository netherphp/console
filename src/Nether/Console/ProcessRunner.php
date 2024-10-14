<?php ##########################################################################
################################################################################

namespace Nether\Console;

use Nether\Common;

################################################################################
################################################################################

class ProcessRunner {

	public string
	$Line;

	public bool
	$Verbose;

	public mixed
	$Proc;

	public array
	$Pipe = [];

	public Common\Datastore
	$Env;

	public function
	__Construct($Line, bool $Verbose=FALSE) {

		$this->Line = $Line;
		$this->Verbose = $Verbose;
		$this->Env = new Common\Datastore;

		return;
	}

	public function
	Run():
	static {

		$this->Proc = proc_open(
			$this->Line,
			[ ['pipe', 'r'], ['pipe', 'w'], ['pipe', 'w'] ],
			$this->Pipe,
			NULL,
			$this->Env->Export()
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

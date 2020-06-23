<?php

namespace Nether\Console\Queue;

use \Ramsey as Ramsey;
use \React as React;

class ServerClient {

	public function
	__Construct(React\Socket\Connection $Socket) {

		$this->UUID = Ramsey\Uuid\Uuid::UUID4()->ToString();
		$this->Socket = $Socket;
		$this->Buffer = '';

		return;
	}

	public function
	BufferAdd(String $Msg):
	self {

		$this->Buffer .= $Msg;
		return $this;
	}

	public function
	BufferDrain():
	?String {

		$Cmd = NULL;
		$Pos = FALSE;
		$EOL = chr(10);

		while(($Pos = strpos($this->Buffer,$EOL)) !== FALSE) {
			$Cmd = trim(substr($this->Buffer,0,$Pos));
			$this->Buffer = substr($this->Buffer,($Pos+1));

			if(!$Cmd)
			continue;

			return $Cmd;
		}

		return NULL;
	}

}

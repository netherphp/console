<?php

namespace Nether\Console\Queue;

use \Ramsey as Ramsey;
use \React  as React;

class ServerClient {
/*//
handles clients connecting to the tcp side to send commands to the
server. mainly just provides a buffer for the networking.
//*/

	public
	$UUID = NULL;
	/*//
	@type String
	//*/

	public
	$Socket = NULL;
	/*//
	@type React\Socket\Connection
	//*/

	public
	$Buffer = NULL;
	/*//
	@type String
	//*/

	public function
	__Construct(React\Socket\Connection $Socket) {
	/*//
	@date 2020-06-23
	//*/

		$this->UUID = Ramsey\Uuid\Uuid::UUID4()->ToString();
		$this->Socket = $Socket;
		$this->Buffer = '';

		return;
	}

	public function
	BufferAdd(String $Msg):
	self {
	/*//
	@date 2020-06-23
	when data is received the server pushes it into our buffer.
	//*/

		$this->Buffer .= $Msg;
		return $this;
	}

	public function
	BufferDrain():
	?String {
	/*//
	@date 2020-06-23
	after data is received and pushed the server will then ask us to drain the
	buffer to find any complete commands ready to be processed. this implements
	a very basic newline based protocol. it doesn't care what the contents are
	only that separate commands are separated by new lines.
	//*/

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

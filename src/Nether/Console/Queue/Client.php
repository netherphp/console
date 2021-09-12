<?php

namespace Nether\Console\Queue;

use Nether;
use React;

use Exception;
use React\Socket\Connector;
use React\Socket\ConnectionInterface;

class Client {

	protected string
	$Host;

	protected int
	$Port;

	protected mixed
	$Socket;

	////////////////////////////////////////////////////////////////
	////////////////////////////////////////////////////////////////

	public function
	__Construct(string $Host='localhost', int $Port=11301) {

		$this->Host = $Host;
		$this->Port = $Port;

		return;
	}

	////////////////////////////////////////////////////////////////
	////////////////////////////////////////////////////////////////

	public function
	Send(string|object|array $Command):
	static {

		$Command = match(TRUE) {
			is_object($Command)=> json_encode($Command),
			is_array($Command)=> json_encode($Command),
			default=> $Command
		};

		(new Connector)
		->Connect("{$this->Host}:{$this->Port}")
		->Then(function(ConnectionInterface $CX) use($Command){
			$CX->Write("{$Command}\n");
			$CX->End();
			return;
		});

		return $this;
	}

}

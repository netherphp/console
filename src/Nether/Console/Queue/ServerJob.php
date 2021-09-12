<?php

namespace Nether\Console\Queue;

use Ramsey;
use Nether;

class ServerJob
extends Nether\Object\Prototype {
/*//
provide a structure to store job data in since we do not force any
specific protocol or data requirements for what can be pushed in.
//*/

	public string
	$UUID;

	public int
	$TimeCreated;

	////////

	public int
	$Tries = 0;

	public mixed
	$Entry = NULL;

	public int
	$TimeTodo = 0;

	////////

	protected function
	OnReady(Nether\Object\Prototype\ConstructArgs $Args):
	void {

		if(!isset($this->TimeCreated))
		$this->TimeCreated = time();

		if(!isset($this->UUID))
		$this->UUID = Ramsey\Uuid\Uuid::UUID4()->ToString();

		return;
	}

}

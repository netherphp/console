<?php

namespace Nether\Console\Struct;

use Nether\Object\Datastore;

class CommandArgs {

	public array
	$Source;

	public Datastore
	$Inputs;

	public Datastore
	$Options;

	public function
	__Construct(?array $Inputs=NULL, ?array $Options=NULL) {

		$this->Inputs = new Datastore(is_array($Inputs) ? $Inputs : []);
		$this->Options = new Datastore(is_array($Options) ? $Options : []);

		return;
	}

}

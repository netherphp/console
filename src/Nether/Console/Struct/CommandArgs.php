<?php

namespace Nether\Console\Struct;

use Nether\Object\Datastore;

class CommandArgs {

	public Datastore
	$Inputs;

	public Datastore
	$Options;

	public function
	__Construct(?array $Inputs=NULL, ?Datastore $Options=NULL) {

		$this->Inputs = new Datastore($Inputs) ?? new Datastore;
		$this->Options = $Options ?? new Datastore;

		return;
	}

}

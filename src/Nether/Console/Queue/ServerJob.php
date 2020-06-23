<?php

namespace Nether\Console\Queue;

use \Ramsey as Ramsey;

class ServerJob {

	public
	$UUID = NULL;

	public
	$Entry = NULL;

	public
	$Tries = 0;

	public function
	__Construct() {
		$this->UUID = Ramsey\Uuid\Uuid::UUID4()->ToString();
		return;
	}

}

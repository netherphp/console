<?php

namespace Nether\Console\Queue;

use \Ramsey as Ramsey;

class ServerJob {
/*//
provide a structure to store job data in since we do not force any
specific protocol or data requirements for what can be pushed in.
//*/

	public
	$UUID = NULL;
	/*//
	@type String
	//*/

	public
	$Tries = 0;
	/*//
	@type Int
	//*/

	public
	$Entry = NULL;
	/*//
	@type Turbo Mixed
	literally whatever data you decided to throw into the queue from
	within your server's OnCommand method.
	//*/

	public function
	__Construct() {
		$this->UUID = Ramsey\Uuid\Uuid::UUID4()->ToString();
		return;
	}

}

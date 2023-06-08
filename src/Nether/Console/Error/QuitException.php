<?php

namespace Nether\Console\Error;

use Exception;

class QuitException
extends Exception {

	public function
	__Construct(int $Code, string $Message='') {
		parent::__Construct($Message, $Code);

		return;
	}

}

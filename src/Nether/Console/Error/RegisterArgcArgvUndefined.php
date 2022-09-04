<?php

namespace Nether\Console\Error;

use Exception;

class RegisterArgcArgvUndefined
extends Exception {

	public function
	__Construct() {
		parent::__Construct('register_argc_argv must be enabled');
		return;
	}

}

<?php

namespace Nether\Console\Meta;

use Attribute;

#[Attribute(Attribute::TARGET_METHOD|Attribute::IS_REPEATABLE)]
class Toggle
extends Option {

	public function
	__Construct(string $Name, string $Text='') {
		parent::__Construct($Name, FALSE, $Text);
		return;
	}

}

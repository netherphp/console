<?php

namespace Nether\Console\Meta;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS|Attribute::TARGET_METHOD|Attribute::IS_REPEATABLE)]
class Value
extends Option {

	public function
	__Construct(string $Name, string $Text='') {
		parent::__Construct($Name, TRUE, $Text);
		return;
	}

}

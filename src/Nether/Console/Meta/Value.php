<?php

namespace Nether\Console\Meta;

use Toaster;
use Nether;

use Attribute;
use Stringable;
use ReflectionMethod;
use ReflectionAttribute;
use Nether\Object\Prototype\MethodInfo;
use Nether\Object\Prototype\MethodInfoInterface;

#[Attribute(Attribute::TARGET_METHOD|Attribute::IS_REPEATABLE)]
class Value
extends Option {

	public string
	$Name;

	public string
	$Text;

	public bool
	$TakesValue;

	public function
	__Construct(string $Name, string $Text='') {

		parent::__Construct($Name, TRUE, $Text);
		return;
	}

}

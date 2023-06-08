<?php

namespace Nether\Console\Meta;

use Toaster;
use Nether;

use Attribute;
use Exception;
use ReflectionMethod;
use ReflectionAttribute;
use Nether\Common\Prototype\MethodInfo;
use Nether\Common\Prototype\MethodInfoInterface;

#[Attribute(Attribute::TARGET_CLASS|Attribute::TARGET_METHOD|Attribute::IS_REPEATABLE)]
class Arg
implements MethodInfoInterface {

	public string
	$Name;

	public string
	$Info;

	////////////////////////////////////////////////////////////////
	////////////////////////////////////////////////////////////////

	public function
	__Construct(string $Name, string $Info="") {

		$this->Name = $Name;
		$this->Info = $Info;

		return;
	}

	public function
	OnMethodInfo(MethodInfo $Info, ReflectionMethod $RefMethod, ReflectionAttribute $RefAttrib) {

		return;
	}

}

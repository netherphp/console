<?php

namespace Nether\Console\Meta;

use Toaster;
use Nether;

use Attribute;
use Exception;
use Stringable;
use ReflectionMethod;
use ReflectionAttribute;
use Nether\Object\Prototype\MethodInfo;
use Nether\Object\Prototype\MethodInfoInterface;

#[Attribute(Attribute::IS_REPEATABLE|Attribute::TARGET_ALL)]
class Arg
implements MethodInfoInterface, Stringable {

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
	__ToString():
	string {

		return $this->Name ?? '<unknown>';
	}

	public function
	OnMethodInfo(MethodInfo $Info, ReflectionMethod $RefMethod, ReflectionAttribute $RefAttrib) {

		return;
	}

}
<?php

namespace Nether\Console\Meta;

use Toaster;
use Nether;

use Attribute;
use ReflectionMethod;
use ReflectionAttribute;
use Nether\Object\Prototype\MethodInfo;
use Nether\Object\Prototype\MethodInfoInterface;

#[Attribute(Attribute::TARGET_METHOD|Attribute::IS_REPEATABLE)]
class Option
implements MethodInfoInterface {

	public string
	$Name;

	public string
	$Text;

	public bool
	$TakesValue;

	public function
	__Construct(string $Name, bool $TakesValue=FALSE, string $Text='') {

		$this->Name = $Name;
		$this->TakesValue = $TakesValue;
		$this->Text = $Text;

		return;
	}

	public function
	OnMethodInfo(MethodInfo $Info, ReflectionMethod $RefMethod, ReflectionAttribute $RefAttrib) {

		return;
	}

}

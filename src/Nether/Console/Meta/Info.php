<?php

namespace Nether\Console\Meta;

use Attribute;
use ReflectionMethod;
use ReflectionAttribute;
use Nether\Common\Prototype\MethodInfo;
use Nether\Common\Prototype\MethodInfoInterface;

#[Attribute]
class Info
implements MethodInfoInterface {

	public ?string
	$Text;

	////////////////////////////////////////////////////////////////
	////////////////////////////////////////////////////////////////

	public function
	__Construct(string $Text) {

		// cheeky nullify.

		$this->Text = trim($Text) ?: NULL;

		return;
	}

	public function
	OnMethodInfo(MethodInfo $Info, ReflectionMethod $RefMethod, ReflectionAttribute $RefAttrib) {

		return;
	}


}

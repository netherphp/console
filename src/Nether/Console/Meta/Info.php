<?php

namespace Nether\Console\Meta;

use Attribute;
use Stringable;
use ReflectionMethod;
use ReflectionAttribute;
use Nether\Object\Prototype\MethodInfo;
use Nether\Object\Prototype\MethodInfoInterface;

#[Attribute]
class Info
implements MethodInfoInterface, Stringable {

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
	__ToString():
	string {

		return $this->Text ?? 'No info provided.';
	}

	public function
	OnMethodInfo(MethodInfo $Info, ReflectionMethod $RefMethod, ReflectionAttribute $RefAttrib) {

		return;
	}


}

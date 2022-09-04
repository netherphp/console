<?php

namespace Nether\Console\Meta;


use Attribute;
use Stringable;
use ReflectionMethod;
use ReflectionAttribute;
use Nether\Object\Prototype\MethodInfo;
use Nether\Object\Prototype\MethodInfoInterface;

#[Attribute(Attribute::TARGET_METHOD|Attribute::IS_REPEATABLE)]
class Error
implements Stringable {

	public int
	$Code;

	public string
	$Text;

	public function
	__Construct(int $Code, string $Text) {

		$this->Code = $Code;
		$this->Text = $Text;
		return;
	}

	public function
	__ToString():
	string {

		return $this->Text ?? 'No info has been provided.';
	}

	public function
	OnMethodInfo(MethodInfo $Info, ReflectionMethod $RefMethod, ReflectionAttribute $RefAttrib) {

		return;
	}

}

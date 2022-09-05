<?php

namespace Nether\Console\Meta;

use Attribute;
use ReflectionMethod;
use ReflectionAttribute;
use Nether\Object\Prototype\MethodInfo;
use Nether\Object\Prototype\MethodInfoInterface;
use Nether\Console\Util;

#[Attribute(Attribute::TARGET_METHOD)]
class Command
implements MethodInfoInterface {

	public string
	$Name;

	public bool
	$Hide;

	////////////////////////////////////////////////////////////////
	////////////////////////////////////////////////////////////////

	public function
	__Construct(?string $Cmd=NULL, bool $Hide=FALSE) {

		// attribute supplied command name for this method.

		if($Cmd !== NULL)
		$this->Name = $Cmd;

		$this->Hide = $Hide;

		return;
	}

	////////////////////////////////////////////////////////////////
	////////////////////////////////////////////////////////////////

	public function
	OnMethodInfo(MethodInfo $Info, ReflectionMethod $RefMethod, ReflectionAttribute $RefAttrib) {

		// self-name if none was specified.

		if(!isset($this->Name))
		$this->Name = Util::PascalToKey($Info->Name);

		return;
	}

}

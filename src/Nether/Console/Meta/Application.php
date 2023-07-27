<?php

namespace Nether\Console\Meta;

use Nether\Common;

use Attribute;
use ReflectionClass;
use ReflectionAttribute;

#[Attribute(Attribute::TARGET_CLASS)]
class Application
implements Common\Prototype\ClassInfoInterface {

	public ?string
	$Name;

	public ?string
	$Version;

	public ?string
	$AutoCmd;

	public ?string
	$Desc;

	////////////////////////////////////////////////////////////////
	////////////////////////////////////////////////////////////////

	public function
	__Construct(string $Name=NULL, string $Version='0.0.0', ?string $AutoCmd=NULL) {

		$this->Name = $Name;
		$this->Version = $Version;
		$this->AutoCmd = $AutoCmd;
		$this->Desc = NULL;

		return;
	}

	////////////////////////////////////////////////////////////////
	// implements Common\Prototype\ClassInfoInterface //////////////

	public function
	OnClassInfo(Common\Prototype\ClassInfo $Info, ReflectionClass $RefClass, ReflectionAttribute $RefAttrib) {

		if(!isset($this->Name))
		$this->Name = $Info->Name;

		return;
	}

}

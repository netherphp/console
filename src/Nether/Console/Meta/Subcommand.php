<?php

namespace Nether\Console\Meta;

use Toaster;
use Nether;

use Attribute;
use Exception;
use Stringable;
use Reflector;
use ReflectionMethod;

#[Attribute]
class Subcommand
implements Stringable {

	protected ?String
	$Name = NULL;

	////////////////////////////////////////////////////////////////
	////////////////////////////////////////////////////////////////

	public function
	__Construct() {

		return;
	}

	public function
	__ToString():
	String {

		return $this->GetName() ?? '<unknown>';
	}

	////////////////////////////////////////////////////////////////
	////////////////////////////////////////////////////////////////

	public function
	BuildFromReflection(Reflector $Reflect):
	static {

		if($Reflect instanceof ReflectionMethod) {
			$this->SetNameFromMethod($Reflect->GetName());
		}

		return $this;
	}

	////////////////////////////////////////////////////////////////
	////////////////////////////////////////////////////////////////

	public function
	GetName():
	?String {
	/*//
	@date 2021-01-05
	//*/

		return $this->Name;
	}

	public function
	GetNameArgsOptions(?Nether\Object\Datastore $Args, ?Nether\Object\Datastore $Options):
	?String {

		$Option = NULL;
		$OptionText = "";

		////////

		if(!$this->Name)
		return NULL;

		if($Args && $Args->Count()) {
			foreach($Args as $Arg)
			$OptionText .= " <{$Arg}>";
		}

		if($Options && $Options->Count()) {
			foreach($Options as $Option)
			$OptionText .= " {$Option}";
		}

		return "{$this->Name}{$OptionText}";
	}

	public function
	SetName(?String $Name):
	static {
	/*//
	@date 2021-01-05
	//*/

		$this->Name = $Name;
		return $this;
	}

	public function
	SetNameFromMethod(?String $Name):
	static {
	/*//
	@date 2021-01-05
	//*/

		return $this->SetName(Nether\Console\Client::GetCommandFromMethod($Name));
	}

}

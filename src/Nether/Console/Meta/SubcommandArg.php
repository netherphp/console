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
class SubcommandArg
implements Stringable {

	protected ?String
	$Name = NULL;

	protected ?String
	$Text = NULL;

	protected Bool
	$TakesValue = FALSE;

	public function
	__Construct(String $Name, Bool $TakesValue=FALSE, String $Text="") {

		$this->Name = $Name;
		$this->TakesValue = $TakesValue;
		$this->Text = $Text;
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
	GetNameValue():
	?String  {

		if(!$this->Name)
		return NULL;

		return "<{$this->Name}>";
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
	GetText():
	?String {
	/*//
	@date 2021-01-05
	//*/

		return $this->Text;
	}

	public function
	SetText(?String $Text):
	static {
	/*//
	@date 2021-01-05
	//*/

		$this->Text = $Text;
		return $this;
	}

	public function
	GetTakesValue():
	?String {
	/*//
	@date 2021-01-05
	//*/

		return $this->TakesValue;
	}

	public function
	SetTakesValue(Bool $TakesValue):
	static {
	/*//
	@date 2021-01-05
	//*/

		$this->TakesValue = $TakesValue;
		return $this;
	}

}

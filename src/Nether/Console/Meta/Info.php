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
class Info
implements Stringable {

	public function
	__Construct(String $Text) {

		$this->Text = $Text;
		return;
	}

	public function
	__ToString():
	String {

		return $this->GetText() ?? 'No info has been provided.';
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

	protected ?String
	$Text = NULL;

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

}

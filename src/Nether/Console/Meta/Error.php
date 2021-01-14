<?php

namespace Nether\Console\Meta;

use Attribute;
use Stringable;
use Reflector;

#[Attribute(Attribute::IS_REPEATABLE|Attribute::TARGET_ALL)]
class Error
implements Stringable {

	public function
	__Construct(Int $Code, String $Text) {

		$this->Code = $Code;
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

	protected ?Int
	$Code = NULL;

	public function
	GetCode():
	?Int {
	/*//
	@date 2021-01-05
	//*/

		return $this->Code;
	}

	public function
	SetCode(?Int $Code):
	static {
	/*//
	@date 2021-01-05
	//*/

		$this->Code = $Code;
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

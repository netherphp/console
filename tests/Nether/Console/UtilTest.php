<?php

namespace NetherTestSuite\Console\Client;
use Nether;
use PHPUnit;

use Nether\Console\Util;

////////////////////////////////////////////////////////////////
////////////////////////////////////////////////////////////////

class UtilTest
extends PHPUnit\Framework\TestCase {

	/** @test */
	public function
	PascalToKey():
	void {

		$Things = [
			'ObviouslyValid'   => 'obviously-valid',
			'ObviouslyValid69' => 'obviously-valid-69',
			'lameCaseSafeToo'  => 'lame-case-safe-too'
		];

		$Old = NULL;
		$New = NULL;

		$this->AssertEquals('', Util::PascalToKey(NULL));

		foreach($Things as $Old => $New)
		$this->AssertEquals($New, Util::PascalToKey($Old));

		return;
	}

	/** @test */
	public function
	ParseCommandOption():
	void {

		$Things = [
			// long form inputs.
			'--valid'       => [ 'valid', TRUE ],
			'--also-valid'  => [ 'also-valid', TRUE ],
			'--input=thing' => [ 'input', 'thing' ],

			// short form inputs.
			'-a=1' => [ 'a', '1' ],
			'-f=2' => [ 'f', '2' ],
			'-k=3' => [ 'k', '3' ],

			// switches/toggles
			'-f' => [ 'f', TRUE ],

			// switch/toggle blocks
			'-afk' => [ 'a', TRUE ],
			'-afk' => [ 'f', TRUE ],
			'-afk' => [ 'k', TRUE ],

			// invalid options.
			'-'  => [ NULL ],
			'--' => [ NULL ]
		];

		$Old = NULL;
		$New = NULL;
		$Result = NULL;

		foreach($Things as $Old => $New) {
			$Result = Util::ParseCommandOption($Old);

			if($New[0] !== NULL) {
				$this->AssertArrayHasKey($New[0], $Result);
				$this->AssertTrue($New[1] === $Result[$New[0]]);
			}

			else {
				$this->AssertNull($Result);
			}
		}

		return;
	}

};

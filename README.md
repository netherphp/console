# **Nether Console (netherphp/console)**

[![Packagist](https://img.shields.io/packagist/v/netherphp/console.svg?style=for-the-badge)](https://packagist.org/packages/netherphp/console) [![Packagist](https://img.shields.io/packagist/dt/netherphp/console.svg?style=for-the-badge)](https://packagist.org/packages/netherphp/console) [![Build Status](https://img.shields.io/github/workflow/status/netherphp/console/Unit%20Tests?style=for-the-badge)](https://github.com/netherphp/console/actions) [![codecov](https://img.shields.io/codecov/c/gh/netherphp/console?style=for-the-badge&token=VQC48XNBS2)](https://codecov.io/gh/netherphp/console)

This package provides some basic functionality for creating command line
interfaces via PHP 8 attributes.




# Quickstart

```php
require('vendor/autoloader.php');

use Nether\Console\Meta\Command;
use Nether\Console\Meta\Info;
use Nether\Console\Meta\Arg;
use Nether\Console\Meta\Toggle;

class App
extends Nether\Console\Client {

	#[Command]
	#[Info('A whatever command.')]
	public function
	Whatever():
	int {

		echo 'Whatever', PHP_EOL;
		return 0;
	}

	#[Command]
	#[Info('A whenever command.')]
	#[Arg('date', 'A date input.')]
	#[Toggle('-u', 'Output as unix time.')]
	public function
	Whenever():
	int {

		$Date = $this->GetInput(1);
		$Unixise = $this->GetOption('u');

		if($Unixise)
		echo date('U', strtotime($Date));
		else
		echo date('Y-m-d', strtotime($Date));

		echo PHP_EOL;

		return 0;
	}

}

exit((new App)->Run());
```

```
$ php ./test.php

USAGE: test.php <command> <args>

  whatever

    A whatever command.

  whenever <date>

    A whenever command.

    -u
      Output as unix time.

```

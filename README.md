# Nether Console

[![nether.io](https://img.shields.io/badge/nether-console-C661D2.svg)](http://nether.io/) [![Build Status](https://travis-ci.org/netherphp/console.svg)](https://travis-ci.org/netherphp/console)  [![Packagist](https://img.shields.io/packagist/v/netherphp/console.svg)](https://packagist.org/packages/netherphp/console) [![Packagist](https://img.shields.io/packagist/dt/netherphp/console.svg)](https://packagist.org/packages/netherphp/console)

A CLI Parser.



# Quickstart

It will take the command line as given and chop it up into `Inputs` and
`Options`. Options are things prefixed with - or --, so they look like
`--option`, `--option=value`, or `--option="long value"`. Inputs are
anythings which are not options.

## Options

Options are accessible via their name with the `GetOption(string Name)`
method. If the option was not defined you will get literal NULL. If it
was defined without a specific value, you will get boolean TRUE. Else you will
get the string that it was given without quotes around it. Options which are one
character long are case sensitive flags (-t, -T, --a, --A), longer options are
case insensitive (--verbose can be accessed by Verbose, verbose, VerBosE).

## Inputs

Inputs are accessible via their offset. To get the first non-option
value that was give you would use `GetInput(1)`, and to get the next
`GetInput(2)` etc. This will work even if your options are peppered in the
middle of the command line.

## Handlers

As for executing things Nether Console works on a series of handlers you
define. These are functions that are called when the input matches. By
default it will attempt to find a handler that matches the value of
`GetInput(1)` if not found it will try to run the handler named `help`.



# Example: Hello Handler Lazy Defined

Here is some code for a command script, called `greet.php`

```php
(new Nether\Console\Client)
->SetHandler('help',function(){
	$this::Messages(
		'Usage: hello <name>',
		'if name is specified, it will greet it. else it will greet the world.'
	);
	return 0;
})
->SetHandler('hello',function(){
	$Who = ($this->GetInput(2))?:('World');
	$this::Message("Hello {$Who}");
	return 0;
})
->Run();
```

When we run that code...

	$ php greet.php
	Usage: hello <name>
	if name is specified, it will greet it. else it will greet the world.

	$ php greet.php hello
	Hello World

	$ php greet.php hello Bob
	Hello Bob



# Example: Handler Handlers

We can also execute other handlers from our handlers. Here is an obviously
too simple use case to demonstrate. It would make more sense to do something
like this when your stuff gets crazy.

```php
(new Nether\Console\Client)
->SetHandler('run',function(){
	$Value = $this->GetInput(2);

	if(!$Value)
	$this::Quit('no input value specified',1);

	$this->Run([ 'run-one-thing', "--value={$Value}" ]);
	$this->Run([ 'run-another-thing', "--value={$Value}" ]);
	$this::Quit('done.');
})
->SetHandler('run-one-thing',function(){
	$Value = $this->GetOption('value');
	// ...
})
->SetHandler('run-another-thing',function(){
	$Value = $this->GetOption('value');
	// ...
})
->Run();
```

And then running this...

	$ php handlers.php run
	no input value specified.

	$ php handlers.php run whatever
	done.

Basically, passing an array to the `Run()` method is the same as having run
the script like this...

	$ php handlers.php run-one-thing --value=whatever
	$ php handlers.php run-another-thing --value=whatever



# Example: Handlers by Extending Console Client

Of course you can always write a more solid application by extending the class
rather than writing all your handlers inline. Instead of using AddHandler you
can create methods prefixed with "Handle" - so to process the command
`php MyConsoleApp.php build something` you would create a method named
`HandleBuild` for it to execute.

```php
class MyConsoleApp
extends Nether\Console\Client {

	public function
	HandleBuild():
	Int {
		$What = $this->GetInput(2);

		if(!$What)
		$this::Quit('no value specified',1);

		$this::Message("consider {$What} built.");
		return 0;
	}

}

(new MyConsoleApp)
->Run();
```


# About Options

All options may be specified with - or -- prefixes.

* `--opt1 --opt2 -o -m -g`

All options may have optional values added by equals.

* `--opt1=omg --opt2=wtf -t="bbq lol"`

Switchblocks are supported, but to pass a value to the last item you still
need the equals sign.

* `-zomg=bbq`

That evaluates as z, o, and m === true, while g === 'bbq'.

Single letter options will retain their case sensitivity, so -t and -T are two
different options. Longer option names are case insensitive.


# Console API

```php

class Nether\Console\Client {

	public function
	GetInput(Int Offset): NULL | String;
	/*//
	fetch the specified input value given to the command by its offset in the
	command line. returns null if not found. assume that options did not
	exist, all inputs are squashed together in order.
	/*//

	public function
	GetInputs(Void): Array;
	/*//
	fetch the array of input values that were given to the command line.
	//*/

	public function
	GetOption(String Name): NULL | TRUE | String;
	/*//
	fetch the specified option by its name. note that single letter
	options are case sensitive where long option names are not.
	//*/

	public function
	GetOptions(Void): Array;
	/*//
	fetch the array of options keyed by their name. all long form options will
	have been converted to lower case names.
	//*/

	public function
	SetChainCommands(Bool $Chain): self;
	/*//
	set if this client should process all inputs as though they are potential
	handlers, or stop after the first one is found. default value is FALSE.
	//*/

	public function
	WillChainCommands(Void): Bool;
	/*//
	returns TRUE if this client intends to process all non-option inputs as
	potential handlers.
	//*/

	public function
	SetHandler(String HandlerName, Callable HandlerFunc): self;
	/*//
	lazy define a handler function to the specified handler name. for serious
	apps you should consider extending this class instead.
	//*/

	public function
	Run(Void): Mixed;
	/*//
	run the client. it will inspect the $_SERVER['argv'] input to decide what
	it should do and how it should do it based on the handlers you have
	defined.
	//*/

	public function
	Run(Array Argv): Mixed;
	/*//
	run this client with the specified array of data as though this data was
	actually passed in via $_SERVER['argv'].
	//*/

	static public function
	Message(String Msg, Array Options=NULL): Void;
	/*//
	prints the specified message to STDOUT with automatic line wrapping,
	automatic indenting, and automatic new lines.

	* Opt[EOL]: String - change the new line character (default PHP_EOL)
	* Opt[Prefix]: Bool - set if should attempt auto indent (default TRUE)
	* Opt[Width]: Int - set auto line wrap column (default 75)

	can be used without having an instance of a client laying around if you
	just want to use it for convenience somewhere.
	//*/

	static public function
	PrintLine(String Msg, Array Options=NULL): Void;
	/*//
	an alias of Message but with line wrapping force disabled.
	//*/

	static public function
	Quit(?String Msg=NULL, Int Error=0): Void;
	/*//
	terminate PHP execute completely, printing the specified message and
	exiting with the specified error number. can be used without having an
	instance of client if you just want to use it for convenience somewhere.
	//*/

}
```


# Testing

Nether\Console uses PHPUnit to test.

	$ composer install
	Installing netherphp/object
	Generating autoload files

	$ phpunit --bootstrap vendor/autoload.php tests
	OK (11 tests, 49 assertions)



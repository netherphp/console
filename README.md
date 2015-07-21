# Nether Console

A CLI Parser.

# Quickstart

It will take the command line as given and chop it up into `Inputs` and
`Options`. Options are things prefixed with - or --, so they look like
`--option`, `--option=value`, or `--option="long value"`. Inputs are
anythings which are not options.

Options are accessible via their name with the `GetOption(string Name)`
method. If the option was not defined you will get boolean false. If it
was defined all alone, you will get boolean true. Else you will get the
string that it was given without quotes around it.

Inputs are accessible via their offset. To get the first non-option
value that was give you would use `GetInput(1)`, and to get the next
`GetInput(2)` etc. This will work even if your options are peppered in the
middle of the command line.

As for executing things Nether Console works on a series of handlers you
define. These are functions that are called when the input matches. By
default it will attempt to find a handler that matches the value of
`GetInput(1)` - and if none are specified it will try to run the handler
named `help`.

# Example: Hello Handler

Here is some code for a command script, called `greet.php`

	$cli = (new Nether\Console\Client)
	->SetHandler('help',function($console){
		$console::Messages(
			'Usage: hello <name>',
			'if name is specified, it will greet it. else it will greet the world.'
		);
	})
	->SetHandler('hello',function($console){
		$who = ($console->GetInput(2))?:('World');
		$console::Message("Hello {$who}");
	})
	->Run();

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

	$cli = (new Nether\Console\Client)
	->SetHandler('run',function($console){
		$value = $console->GetInput(2);
		if(!$value) {
			$console::Message('no input value specified');
			$console::Quit();
		}

		$console->Run([ 'run-one-thing', "--value={$value}" ]);
		$console->Run([ 'run-another-thing', "--value={$value}" ]);
		$console::Message('done.');
	})
	->SetHandler('run-one-thing',function($console){
		$value = $console->GetOption('value');
		// ...
	})
	->SetHandler('run-another-thing,function($console){
		$value = $console->GetOption('value');
		// ...
	})
	->Run();


# About Options

All options may be specified with - or -- prefixes.

* `php whatever.php --opt1 --opt2 -o -m -g`

All options may have optional values added by equals.

* `php whatever.php --opt1=omg --opt2=wtf -t="bbq lol"`

Switch blocks are not currently supported. Example, `-omg` is an option named
'omg' not -o -m and -g.

# Console API

* `Console->GetInput(int Offset)`
  fetches the nth non-option argument that existed.
* `Console->GetInputs(void)`
  fetch all the non-option arguments.
* `Console->GetOption(string Name)`
  fetch the specified option.
* `Console->GetOptions(void)`
  fetch all the option arguments.
* `Console::Message(string Msg)`
  print a message out.
* `Console::Messages(string Msg, ...)`
  print a theoretically infinite number of messages out, one after another.

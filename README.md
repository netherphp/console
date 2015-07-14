# Nether Console

A CLI Parser.

# Using Console

Given a command line like...

* `php whatever.php build path/to/file --output=newfile.txt --verbose`
* `php whatever.php --output=newfile.txt build --verbose path/to/file`
* `php whatever.php --output=newfile.txt --verbose build path/to/file`

We could do this.

	$cli = new Nether\Console\Client;

	switch($cli->GetInput(1)) {
		case 'build': {
			$infile = $cli->GetInput(2);
			if(!$infile) die('no input file specified');

			$outfile = $cli->GetOption('output');
			if(!$outfile) die('no output file specified');

			if($cli->GetOption('verbose')) {
				echo 'debugging output enabled',PHP_EOL;
			}

			// ...

			break;
		}
	}

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

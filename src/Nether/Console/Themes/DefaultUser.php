<?php

namespace Nether\Console\Themes;

use Nether\Console;

class DefaultUser
extends Console\Theme {

	public array
	$Default = [ ];

	public array
	$Primary = [ 'Bold'=> TRUE, 'Colour'=> '#7B9BD5' ];

	public array
	$Accent = [ 'Bold'=> TRUE, 'Colour'=> '#9EB2D7' ];

	public array
	$OK = [ 'Bold'=> TRUE, 'Colour'=> '#ABD57B' ];

	public array
	$Error = [ 'Bold'=> TRUE, 'Colour'=> '#E95353' ];

	public array
	$Warning = [ 'Bold'=> TRUE, 'Colour'=> '#E9CE53' ];

	public array
	$Alert = [ 'Bold'=> TRUE, 'Colour'=> '#E953DD' ];

	public array
	$Strong = [ 'Bold'=> TRUE ];

	public array
	$Muted = [ 'Colour'=> '#666666' ];

	////////

	public string
	$CharBullet = '•';

	public string
	$CharHeader = '█';

};

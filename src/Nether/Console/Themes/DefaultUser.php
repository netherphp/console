<?php

namespace Nether\Console\Themes;

use Nether\Console;

class DefaultUser
extends Console\Theme {

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
	$Muted = [ 'Colour'=> '#676767' ];

};

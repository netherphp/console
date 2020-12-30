<?php

namespace Nether\Console\Crontab;

use Nether;

use Exception;

class Manager {

	public
	Nether\Object\Datastore $List;

	////////////////////////////////////////////////////////////////
	////////////////////////////////////////////////////////////////

	public function
	__Construct() {
	/*//
	@date 2020-12-30
	//*/

		$this->List = new Nether\Object\Datastore;

		return;
	}

	////////////////////////////////////////////////////////////////
	////////////////////////////////////////////////////////////////

	public function
	Append(String|Entry $Line):
	static {
	/*//
	@date 2020-12-30
	//*/

		$Entry = NULL;

		////////

		if($Line instanceof Entry)
		$Entry = $Line;

		else
		try { $Entry = new Entry($Line); }
		catch(Exception $Error) { $Entry = trim($Line); }

		////////

		$this->List->Push($Entry);
		return $this;
	}

	public function
	Install(Array ...$Argv):
	static {
	/*//
	@date 2020-12-30
	@todo may have config for different install types.
	//*/

		return $this->InstallByPipe(...$Argv);
	}

	public function
	InstallByPipe(?String $Cmd=NULL):
	static {
	/*//
	@date 2020-12-30
	//*/

		$Cmd ??= 'crontab -';
		$Process = NULL;
		$PipeConfig = [ ['pipe','r'],['pipe','w'],['pipe','r'] ];
		$Pipe = NULL;

		////////

		$Process = proc_open($Cmd,$PipeConfig,$Pipe);

		if(!$Process)
		throw new Exception('error opening pipe');

		foreach($this->List as $Entry)
		fwrite($Pipe[0],"{$Entry}\n");

		proc_close($Process);

		/////////

		return $this;
	}

	public function
	GetEntries():
	Nether\object\Datastore {
	/*//
	@date 2020-12-30
	//*/

		return ($this->List)
		->Distill(function(Mixed $Val){ return $Val instanceof Entry; })
		->Reindex();
	}

	////////////////////////////////////////////////////////////////
	////////////////////////////////////////////////////////////////

	static public function
	Load():
	static {
	/*//
	@date 2020-12-30
	@todo may have config for loading types.
	//*/

		return static::LoadFromCLI();
	}

	static public function
	LoadFromCLI(?String $Cmd=NULL):
	static {
	/*//
	@date 2020-12-30
	//*/

		$Output = new static;
		$Cmd ??= 'crontab -l';
		$Exit = NULL;
		$Stdout = [];
		$Line = NULL;

		////////

		exec($Cmd,$Stdout,$Exit);

		foreach($Stdout as $Line)
		$Output->Append($Line);

		////////

		return $Output;
	}

}

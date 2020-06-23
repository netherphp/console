<?php

namespace Nether\Console\Queue;

use \React as React;
use \Nether as Nether;

use \Throwable as Throwable;

class ServerHost {

	protected
	$Queue = NULL;

	protected
	$Workers = [];

	protected
	$MaxWorkers = 2;

	protected
	$Bind = NULL;

	protected
	$Quiet = NULL;

	public function
	__Construct($Opt=NULL) {

		$Opt = new Nether\Object\Mapped($Opt,[
			'Bind'       => '127.0.0.1:11301',
			'Datafile'   => NULL,
			'MaxWorkers' => 2,
			'Quiet'      => FALSE
		]);

		$this->Bind = $Opt->Bind;
		$this->MaxWorkers = (Int)$Opt->MaxWorkers;
		$this->Queue = new Nether\Object\Datastore;
		$this->Quiet = $Opt->Quiet;

		if($Opt->Datafile)
		$this->PrepareDatafile($Opt->Datafile);

		if(!$this->Quiet)
		echo ">> Queue Length: {$this->Queue->Count()}, Max Workers: {$this->MaxWorkers}", PHP_EOL;

		$this->Run();
		return;
	}

	protected function
	PrepareDatafile(?String $Datafile):
	Void {

		$Error = NULL;
		$WriteError = NULL;

		if(!$Datafile)
		return;

		try {
			if(!$this->Quiet)
			echo ">> Loading {$Datafile}", PHP_EOL;

			($this->Queue)
			->SetFilename($Datafile)
			->Read();
		}

		catch(Throwable $Error) {
			if($Error->GetCode() === 1) {
				if(!$this->Quiet)
				echo ">> Creating {$Datafile}", PHP_EOL;

				try { $this->Queue->Write($Datafile); }
				catch(Throwable $WriteError) { echo $WriteError->GetMessage(), PHP_EOL; }
			}

			else
			echo $Error->GetMessage(), PHP_EOL;
		}

		return;
	}

	public function
	Run() {

		$this->Loop = React\EventLoop\Factory::Create();
		$this->Server = new React\Socket\Server($this->Bind,$this->Loop);

		if(!$this->Quiet)
		echo ">> Server: {$this->Bind}", PHP_EOL;

		($this->Loop)
		->AddTimer(1,[$this,'QueueKick']);

		($this->Server)
		->On('connection',[$this,'OnOpen']);

		$this->Loop->Run();
		return;
	}

	public function
	OnOpen(React\Socket\Connection $Connection):
	Void {

		$Client = new ServerClient($Connection);

		($Client->Socket)
		->On('close',function() use($Client) { return $this->OnClose($Client); })
		->On('data',function($Input) use($Client) { return $this->OnRecv($Client,$Input); });

		if(!$this->Quiet)
		echo ">> Connect: {$Client->Socket->GetRemoteAddress()}", PHP_EOL;

		return;
	}

	public function
	OnClose(ServerClient $Client):
	Void {

		if(!$this->Quiet)
		echo ">> Disconnect: {$Client->Socket->GetRemoteAddress()}", PHP_EOL;

		return;
	}

	public function
	OnRecv(ServerClient $Client, String $Data):
	Void {

		$Command = NULL;

		$Client->BufferAdd($Data);

		while($Command = $Client->BufferDrain())
		$this->OnCommand($Client,$Command);

		return;
	}

	public function
	OnCommand(ServerClient $Client, String $Command):
	Void {
	/*//
	@template
	//*/

		if(!$this->Quiet)
		echo ">> Command: {$Client->Socket->GetRemoteAddress()} {$Command}", PHP_EOL;

		$this->QueuePush($Command);
		return;
	}

	public function
	OnJob(ServerJob $Job):
	Void {
	/*//
	@template
	//*/

		if(!$this->Quiet)
		echo ">> Job {$Job->UUID} Start", PHP_EOL;

		if(!$this->Quiet)
		echo ">> Job {$Job->UUID} Done", PHP_EOL;

		$this->JobDone($Job);
		return;
	}

	public function
	JobDone(ServerJob $Job):
	Void {

		unset($this->Workers[$Job->UUID]);

		if(!$this->Quiet)
		echo ">> Worker Done, Active: ", count($this->Workers), ", Queue Size: {$this->Queue->Count()}", PHP_EOL;

		$this->QueueKick();

		return;
	}

	public function
	JobRetry(ServerJob $Job):
	Void {

		unset($this->Workers[$Job->UUID]);
		$this->QueuePush($Job->Entry);

		return;
	}

	public function
	QueuePush($Entry):
	Void {

		$Job = new ServerJob;
		$Job->Entry = $Entry;

		($this->Queue)
		->Push($Job)
		->Write();

		if(!$this->Quiet)
		echo ">> Queue Push: {$Job->UUID}, Current Size: {$this->Queue->Count()}", PHP_EOL;

		if(count($this->Workers) < $this->MaxWorkers)
		$this->QueueKick();

		return;
	}

	public function
	QueueNext():
	?ServerJob {

		$Job = $this->Queue->Shift();
		$this->Queue->Write();

		return $Job;
	}

	public function
	QueueKick():
	Void {

		if(!$this->Queue->Count()) {
			return;
		}

		$Job = $this->QueueNext();

		$this->Workers[$Job->UUID] = $Job;
		$Job->Tries += 1;

		if(!$this->Quiet)
		echo ">> Worker Start: {$Job->UUID}, Active: ", count($this->Workers), PHP_EOL;

		$this->OnJob($Job);

		if(count($this->Workers) < $this->MaxWorkers)
		$this->QueueKick();

		return;
	}

	public function
	RunChildProcess(Array $Opt):
	Void {

		$Opt = new Nether\Object\Mapped($Opt,[
			'Job'       => NULL,
			'Command'   => NULL,
			'Requeue'   => FALSE,
			'OnExit'    => NULL,
			'OnMessage' => NULL
		]);

		if(!($Opt->Job instanceof ServerJob)) {
			echo ">> Run Child Process: Missing Job", PHP_EOL;
			return;
		}

		echo "[{$Opt->Job->UUID}] Start: {$Opt->Command}", PHP_EOL;

		$Task = new React\ChildProcess\Process($Opt->Command);
		$Task->Start($this->Loop);

		$Task->On('exit',function(Int $Errno) use($Opt){

			if($Errno !== 0 && $Opt->Requeue) {
				echo "[{$Opt->Job->UUID}] Requeue: {$Errno} {$Opt->Job->Tries}", PHP_EOL;
				$this->JobRetry($Opt->Job);
			}

			else {
				echo "[{$Opt->Job->UUID}] Done: {$Errno}", PHP_EOL;
				$this->JobDone($Opt->Job);
			}

			return;
		});

		$Task->stdout->On('data',function(String $Input) use($Opt){

			if(!$this->Quiet && trim($Input))
			echo "[{$Opt->Job->UUID}] Said: ", trim($Input), PHP_EOL;

			return;
		});

		return;
	}

}

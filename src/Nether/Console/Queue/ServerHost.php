<?php

namespace Nether\Console\Queue;

use \React  as React;
use \Nether as Nether;
use \Ramsey as Ramsey;
use React\EventLoop\Loop;
use \Throwable as Throwable;

class ServerHost {
/*//
this class provides a basic queue service. its features include tcp connection
handling so that commands may be sent to the queue, automatic queue processing
with multiple worker support, and persistent storage of the queue so that if
it needed to be shutdown while there were still things waiting, that those jobs
can be brought back next time you start the service.

intended use case:
	* class YourProjectQueue extends ServerHost
	* override OnCommand to parse the commands you want to send to it via tcp.
	* override OnJob to handle your parsed commands and do the work.
	* execute it from a cli script, new YourProjectQueue;
//*/

	protected
	$Queue = NULL;
	/*//
	@type Nether\Object\Datastore
	//*/

	protected
	$Workers = [];
	/*//
	@type Array
	//*/

	protected
	$MaxWorkers = 0;
	/*//
	@type Int
	//*/

	protected
	$Bind = NULL;
	/*//
	@type String "host:port"
	//*/

	protected
	$Quiet = NULL;
	/*//
	@type Bool
	//*/

	protected
	$UUID = NULL;
	/*//
	@type String
	//*/

	protected
	$DateFormat = NULL;
	/*//
	@type String
	//*/

	protected int
	$PulseFreq = 30;

	public function
	__Construct($Opt=NULL) {
	/*//
	@date 2020-06-23
	//*/

		$Opt = new Nether\Object\Mapped($Opt,[
			'Bind'       => '127.0.0.1:11301',
			'Datafile'   => 'queue.phson',
			'MaxWorkers' => 2,
			'Quiet'      => FALSE,
			'UUID'       => Ramsey\Uuid\Uuid::UUID4()->ToString(),
			'DateFormat' => 'Y-m-d H:i:s'
		]);

		$this->Bind = $Opt->Bind;
		$this->MaxWorkers = (Int)$Opt->MaxWorkers;
		$this->Queue = new Nether\Object\Datastore;
		$this->Quiet = $Opt->Quiet;
		$this->UUID = $Opt->UUID;
		$this->DateFormat = $Opt->DateFormat;

		if($Opt->Datafile)
		$this->PrepareDatafile($Opt->Datafile);

		$this->PrintLn(sprintf(
			'Queue Length: %s, Max Workers: %s',
			$this->Queue->Count(),
			$this->MaxWorkers
		));

		$this->Run();
		return;
	}

	protected function
	PrepareDatafile(?string $Datafile):
	void {
	/*//
	@date 2020-06-23
	try to load the datafile to disk to prime the queue with any jobs that were
	not executed yet when it was shutdown.
	//*/

		$Error = NULL;
		$WriteError = NULL;

		if(!$Datafile)
		return;

		try {
			$this->PrintLn(sprintf('Loading %s',$Datafile));

			($this->Queue)
			->SetFilename($Datafile)
			->Read();
		}

		catch(Throwable $Error) {
			if($Error->GetCode() === 1) {
				$this->PrintLn(sprintf('Creating %s',$Datafile));

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
	/*//
	@date 2020-06-23
	perform the actual execution of the queue where we hand off program
	control to react.
	//*/

		$this->Loop = Loop::Get();

		////////

		try {
			$this->Server = new React\Socket\SocketServer($this->Bind);
		}

		catch(Throwable $Error) {
			$this->PrintLn(sprintf(
				'Error Starting Service: %s',
				$Error->GetMessage()
			));
			return;
		}

		////////

		($this->Server)
		->On('connection',[$this,'OnOpen']);

		$this->PrintLn(sprintf(
			'Server: %s, Listening: %s',
			$this->UUID,
			$this->Bind
		));

		// once the queue is up we will want to have it kick off any jobs
		// that were still pending.

		($this->Loop)
		->AddTimer(0.25,function(){
			$this->PrintLn('Queue Init');
			$this->QueueKick();
			return;
		});

		($this->Loop)
		->AddPeriodicTimer($this->PulseFreq,function(){
			$this->PrintLn('Queue Heartbeat');
			$this->QueueKick();
			return;
		});

		return;
	}

	public function
	OnOpen(React\Socket\Connection $Connection):
	void {
	/*//
	@date 2020-06-23
	tcp client connect.
	//*/

		$Client = new ServerClient($Connection);

		($Client->Socket)
		->On('close',function() use($Client) { return $this->OnClose($Client); })
		->On('data',function($Input) use($Client) { return $this->OnRecv($Client,$Input); });

		$this->PrintLn(sprintf('Connect: %s',$Client->Socket->GetRemoteAddress()));

		return;
	}

	public function
	OnClose(ServerClient $Client):
	void {
	/*//
	@date 2020-06-23
	tcp client disconnect.
	//*/

		$this->PrintLn(sprintf('Disconnect: %s',$Client->Socket->GetRemoteAddress()));
		return;
	}

	public function
	OnRecv(ServerClient $Client, string $Data):
	void {
	/*//
	@date 2020-06-23
	when the tcp input gets data throw it into the buffer and then
	see if we have managed to assemble a full command yet.
	//*/

		$Command = NULL;

		$Client->BufferAdd($Data);

		while($Command = $Client->BufferDrain())
		$this->OnCommand($Client,$Command);

		return;
	}

	////////////////////////////////////////////////////////////////
	////////////////////////////////////////////////////////////////

	public function
	OnCommand(ServerClient $Client, string $Command):
	void {
	/*//
	@date 2020-06-23
	@template
	this method gets executed when a full command (newline based protocol)
	is assembled and ready to be processed. generally what you would do is
	overwrite this method with your own implementation to parse the data
	of the command and push it into the queue in a meaningful way.
	//*/

		// this default implementation is literally just pushing the command
		// string into the queue.

		// example 1 part 1
		// a more useful implementation would be, the command is actually json
		// data, so you parse it here and then push it into the queue.

		$this->PrintLn(sprintf(
			'Command: %s %s',
			$Client->Socket->GetRemoteAddress(),
			$Command
		));

		$this->QueuePush($Command);
		return;
	}

	public function
	OnJob(ServerJob $Job):
	void {
	/*//
	@date 2020-06-23
	@template
	this method gets executed when it is time for a job to start doing
	its work. generally what you would do is overwrite this method with
	your own implementation that can parse whatever it was you pushed
	into the queue and do it.
	//*/

		// this default implementation is literally just getting the strings
		// the default OnCommand shoved into the queue and... doing... it.
		// it is important that you flag the job as done when it is done so
		// that it worker can be released to do the next job.

		// example 1 part 2
		// a more useful implementation would be looking at the json that
		// was parsed, determining which script file it describes, and using
		// the RunChildProcess to execute it in a non-blocking way, which will
		// also flag the job as done when done automatically.

		$this->PrintLn(sprintf(
			'OnJob: %s %s',
			$Job->UUID,
			$Job->Entry
		));

		$this->JobDone($Job);
		return;
	}

	////////////////////////////////////////////////////////////////
	////////////////////////////////////////////////////////////////

	public function
	JobDone(ServerJob $Job):
	void {
	/*//
	@date 2020-06-23
	unregister the finished worker and kick off the next job.
	//*/

		unset($this->Workers[$Job->UUID]);

		$this->PrintLn(sprintf(
			'Worker Done: %s, Active: %d, Queue Size: %d',
			$Job->UUID,
			count($this->Workers),
			$this->Queue->Count()
		));

		$this->QueueKick();
		return;
	}

	public function
	JobRetry(ServerJob $Job):
	void {
	/*//
	@date 2020-06-23
	unregister the finished worker, push this job back into the queue,
	which also kicks off the next job.
	//*/

		unset($this->Workers[$Job->UUID]);

		$this->PrintLn(sprintf(
			'Worker Done: %s, Active: %d, Queue Size: %d',
			$Job->UUID,
			count($this->Workers),
			$this->Queue->Count()
		));

		$this->QueuePush($Job->Entry);

		return;
	}

	////////////////////////////////////////////////////////////////
	////////////////////////////////////////////////////////////////

	public function
	RunChildProcess(array $Opt):
	void {
	/*//
	@date 2020-06-23
	the meat of the trick to the async processing. push out a child process
	that actually does the work.
	//*/

		$Opt = new Nether\Object\Mapped($Opt,[
			'Job'       => NULL,
			'Command'   => NULL,
			'Requeue'   => FALSE,
			'OnExit'    => NULL,
			'OnMessage' => NULL
		]);

		if(!($Opt->Job instanceof ServerJob)) {
			$this->PrintLn('Run Child Prcess: Missing Job');
			return;
		}

		$this->PrintLn(sprintf(
			'Job Start: %s',
			$Opt->Job->UUID
		));

		$Task = new React\ChildProcess\Process($Opt->Command);
		$Task->Start(Loop::Get());

		$Task->On('exit',function(Int $Errno) use($Opt){

			if($Errno !== 0 && $Opt->Requeue) {
				$this->PrintLn(sprintf(
					'Job Requeue: %s, Exit: %d, Tries: %d',
					$Opt->Job->UUID,
					$Errno,
					$Opt->Job->Tries
				));
				$this->JobRetry($Opt->Job);
			}

			else {
				$this->PrintLn(sprintf(
					'Job Done: %s, Exit: %d, Tries: %d',
					$Opt->Job->UUID,
					$Errno,
					$Opt->Job->Tries
				));
				$this->JobDone($Opt->Job);
			}

			return;
		});

		$Task->stdout->On('data',function(String $Input) use($Opt){

			if($Input = trim($Input))
			$this->PrintLn(sprintf(
				'Job Msg: %s, Said: %s',
				$Opt->Job->UUID,
				$Input
			));

			return;
		});

		return;
	}

	////////////////////////////////////////////////////////////////
	////////////////////////////////////////////////////////////////

	public function
	QueuePush(mixed $Entry, int $TimeTodo=0):
	void {
	/*//
	@date 2020-06-23
	push some content into the queue. wraps it with a job object
	to uniquely identify it but the format of the content is your
	own to invent and validate.
	//*/

		$Job = new ServerJob;
		$Job->Entry = $Entry;
		$Job->TimeTodo = $TimeTodo;

		($this->Queue)
		->Push($Job)
		->Write();

		$this->PrintLn(sprintf(
			'Queue Push: %s, Queue Size: %d',
			$Job->UUID,
			$this->Queue->Count()
		));

		if(count($this->Workers) < $this->MaxWorkers)
		$this->QueueKick();

		return;
	}

	public function
	QueueNext():
	?ServerJob {
	/*//
	@date 2020-06-23
	swipe the next job off the top of the todo list.
	//*/

		$Job = (
			($this->Queue)
			->Distill(fn(ServerJob $A)=> ($A->TimeTodo <= time()))
			->Shift()
		);

		($this->Queue)
		->Filter(fn(ServerJob $A)=> $A !== $Job)
		->Write();

		return $Job;
	}

	public function
	QueueKick():
	void {
	/*//
	@date 2020-06-23
	kick queue processing off if nothing is going on until the
	max numer of workers are used.
	//*/

		while(count($this->Workers) < $this->MaxWorkers) {
			if(!($Job = $this->QueueNext()))
			break;

			$this->Workers[$Job->UUID] = $Job;
			$Job->Tries += 1;

			$this->PrintLn(sprintf(
				'Worker Start: %s, Active: %d',
				$Job->UUID,
				count($this->Workers)
			));

			$this->OnJob($Job);
		}

		return;
	}

	////////////////////////////////////////////////////////////////
	////////////////////////////////////////////////////////////////

	public function
	PrintLn(string $Content):
	void {
	/*//
	@date 2020-06-26
	printing output to the console if not silenced.
	//*/

		if($this->Quiet)
		return;

		printf(
			'[%s] %s%s',
			date($this->DateFormat),
			$Content,
			PHP_EOL
		);

		return;
	}

}

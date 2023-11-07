<?php

namespace Nether\Console;

////////////////////////////////////////////////////////////////////////////////
////////////////////////////////////////////////////////////////////////////////

use Nether\Common;

use Exception;

////////////////////////////////////////////////////////////////////////////////
////////////////////////////////////////////////////////////////////////////////

class CommandLibrary {

	static public function
	FromNote(string $Input, iterable $Tokens=[]):
	?string {

		$Match = NULL;

		if(preg_match('/^@git-shove (.+)$/ms', $Input, $Match))
		return static::FromString(
			static::GitShoveReckless($Match[1]),
			$Tokens
		);

		return $Input;
	}

	static public function
	FromString(string $Input, iterable $Tokens=[]):
	?string {

		$Output = $Input;

		Common\Datastore::FromArray($Tokens)
		->Each(function(string $New, string $Old) use(&$Output) {

			$Output = str_replace(
				Common\Text::TemplateMakeToken($Old),
				$New,
				$Output
			);

			return;
		});

		return $Output;
	}

	////////////////////////////////////////////////////////////////
	////////////////////////////////////////////////////////////////

	static public function
	GitShoveReckless(string $RepoPath, string $CommitMsg='thusfar'):
	string {

		$Fmt = (
			'git -C %1$s add . 2>&1 ' .
			'&& git -C %1$s commit -m %2$s 2>&1 ' .
			'&& git -C %1$s push 2>&1'
		);

		$CommitMsg = escapeshellarg($CommitMsg);
		$RepoPath = escapeshellarg($RepoPath);

		return sprintf($Fmt, $RepoPath, $CommitMsg);
	}

	////////////////////////////////////////////////////////////////
	////////////////////////////////////////////////////////////////

	static public function
	DidItReallyFailTho(Struct\CommandLineUtil $Result):
	int {

		$Cmd = $Result->Command;
		$Out = $Result->GetOutputString();

		////////

		if(str_starts_with($Result->Command, 'git '))
		return static::DidItReallyFailTho_GitEdition($Result, $Cmd, $Out);

		////////

		return $Result->Error;
	}

	static public function
	DidItReallyFailTho_GitEdition(Struct\CommandLineUtil $Result, string $Cmd, string $Out):
	int {

		if(str_contains($Cmd, ' push')) {
			if(str_contains($Out, 'nothing to commit, working tree clean'))
			return 0;
		}

		return $Result->Error;
	}

};

<?php

namespace FreePBX;

use Symfony\Component\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;

class FreePBXCheckerApplication extends Application {
	protected function getCommandName(InputInterface $input) {
		return 'fpbxseccheck';
	}
	protected function getDefaultCommands() {
		$defaultCommands = parent::getDefaultCommands();
		$defaultCommands[] = new FreePBXCheckerCommand();
		return $defaultCommands;
	}
	public function getDefinition() {
		$inputDefinition = parent::getDefinition();
		$inputDefinition->setArguments();
		return $inputDefinition;
	}
}

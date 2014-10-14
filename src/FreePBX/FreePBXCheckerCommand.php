<?php
namespace FreePBX;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;

class FreePBXCheckerCommand extends Command {
	protected function configure() {
		$this
			->setName('fpbxseccheck')
			->setDescription('Check the FreePBX System')
			->addOption(
			'clean',
			'c',
			InputOption::VALUE_NONE,
			'Automatically attempt to clean up a compromised system'
			)
			->addOption(
			'redownload',
			'r',
			InputOption::VALUE_NONE,
			'Automatically redownload any invalidly signed modules'
			)
			->addOption(
			'module',
			'm',
			InputOption::VALUE_REQUIRED,
			'Check an Individual Module'
			);
	}
	protected function execute(InputInterface $input, OutputInterface $output) {
		$style = new OutputFormatterStyle('white', 'red', array('bold', 'blink'));
		$output->getFormatter()->setStyle('fire', $style);

		$path = dirname(__DIR__);
		include $path.'/GPG.class.php';
		include $path.'/GetConf.class.php';
		include $path.'/CheckFramework.class.php';

		if ($input->getOption('module')) {
			$mod = $input->getOption('module');
			include $path.'/moddetails.php';
		} else {
			$clean = $input->getOption('clean');
			$redownload = $input->getOption('redownload');
			include $path.'/checkall.php';
		}

		$output->writeln("End");
	}
}

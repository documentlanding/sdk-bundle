<?php

namespace DocumentLanding\SdkBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class InstallSdkRequirementsCommand extends ContainerAwareCommand
{

    protected function configure()
    {
        $this
            ->setName('documentlanding:installSdkRequirements')
            ->setDescription('Creates Folder and Symlink for Dynamic Entity')
            ->addArgument('app_dir', InputArgument::REQUIRED, 'App Directory')
            ->addArgument('bundle_dir', InputArgument::REQUIRED, 'SdkBundle Directory')
            ->addArgument('setup_acl', InputArgument::OPTIONAL, '[Unimplemented] Create Target with multi-user ACL rather than 0777')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
	    $container = $this->getContainer();
	    $appDir = $input->getArgument('app_dir');
	    $bundleDir = $input->getArgument('bundle_dir');
	    $setupAcl = $input->getArgument('setup_acl');

	    $target = $appDir . '/Resources/DocumentLanding/sdk-bundle/Entity';
	    $symlink = $bundleDir . '/Entity';

	    // $setupAcl is presently unimplemented.
	    // Will wait for client demand or additional input to make the case.
	    
	    $output->writeln('Document Landing SDK: Installing Requirements');

	    if (!is_dir($target)) {
		    $output->writeln('Document Landing SDK: Creating Target Directory');
		    mkdir($target, 0777, true);
		}
		else {
			$output->writeln('Document Landing SDK: Target Directory Already Exists');
		}

	    if (!is_dir($symlink)) {
		    $output->writeln('Creating Document Landing SDK Symlink');
		    symlink($target, $symlink);
	    }
	    else {
		    $output->writeln('Document Landing SDK: Symlink Already Exists');
	    }

    }

}

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
//	    $appDir = $input->getArgument('app_dir');
	    $bundleDir = $input->getArgument('bundle_dir');
	    $setupAcl = $input->getArgument('setup_acl');

        // Symlinks don't work right with absolute path.  Don't know why.
	    $appDir = $container->get('kernel')->getRootDir();

	    // $setupAcl is presently unimplemented.
	    // Will wait for client demand or additional input to make the case.
	    
	    $output->writeln('Document Landing SDK: Installing Requirements');


        /**
	     * Symlink Entity Folder
	     */

	    $target = $appDir . '/Resources/DocumentLanding/SdkBundle/Entity';
	    $symlink = $bundleDir . '/../Entity';
	     
	    if (!is_dir($target)) {
		    $output->writeln('Document Landing SDK: Creating Entity Target Directory');
		    mkdir($target, 0777, true);
		}
		else {
			$output->writeln('Document Landing SDK: Target Entity Directory Already Exists');
		}

	    if (!is_link($symlink)) {
		    $output->writeln('Creating Document Landing SDK Entity Symlink');
		    @symlink($target, $symlink);
	    }
	    else {
		    $output->writeln('Document Landing SDK: Entity Symlink Already Exists');
	    }

	    chmod($symlink, 0777);


        /**
	     * Symlink Config Folder
	     */

	    $target = $appDir . '/Resources/DocumentLanding/SdkBundle/Resources/config';
	    $symlink = $bundleDir . '/../Resources/config';
	    
	    if (!is_dir($target)) {
		    $output->writeln('Document Landing SDK: Creating Config Target Directory');
		    mkdir($target, 0777, true);
		}
		else {
			$output->writeln('Document Landing SDK: Target Config Directory Already Exists');
		}
		
	    if (!is_link($symlink)) {
		    $output->writeln('Document Landing SDK: Creating Config Symlink');
		    symlink($target, $symlink);
	    }
	    else {
		    $output->writeln('Document Landing SDK: Config Symlink Already Exists');
	    }

	    chmod($symlink, 0777);

        /**
	     * Add Doctrine to Config Folder
	     */
	    
	    $target = $target . '/doctrine';
	    
	    if (!is_dir($target)) {
		    $output->writeln('Document Landing SDK: Creating Doctrine Directory');
		    mkdir($target, 0777, true);
		}
		else {
			$output->writeln('Document Landing SDK: Doctrine Directory Already Exists');
		}

	    chmod($target, 0777);

        /**
	     * Symlink Translations Folder
	     */

	    $target = $appDir . '/Resources/DocumentLanding/SdkBundle/Resources/translations';
	    $symlink = $bundleDir . '/../Resources/translations';
	    
	    if (!is_dir($target)) {
		    $output->writeln('Document Landing SDK: Creating Translations Target Directory');
		    mkdir($target, 0777, true);
		}
		else {
			$output->writeln('Document Landing SDK: Target Translations Directory Already Exists');
		}
		
	    if (!is_link($symlink)) {
		    $output->writeln('Creating Document Landing SDK Translations Symlink');
		    @symlink($target, $symlink);
	    }
	    else {
		    $output->writeln('Document Landing SDK: Translations Symlink Already Exists');
	    }

	    chmod($symlink, 0777);


    }

}

<?php

namespace Symforce\CoreBundle\Command;

use Assetic\Asset\AssetCollectionInterface;
use Assetic\Asset\AssetInterface;
use Assetic\Util\VarUtils;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Dumps assets to the filesystem.
 *
 * @author Kris Wallsmith <kris@symfony.com>
 */
class TestCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('sf:core:test')
            ->setDescription('test code')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $factor  = $this->getContainer()->get('sf.annotation.factory');
        $factor  = $this->getContainer()->get('sf.assets.factory');
        \Dev::dump($factor) ;
    }
}

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
        $compiler  = $this->getContainer()->get('sf.compiler.annotation');
        $compiler  = $this->getContainer()->get('sf.compiler.assets');
        $compiler  = $this->getContainer()->get('sf.compiler.event');
        \Dev::dump($compiler) ;
    }
}

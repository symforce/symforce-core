<?php

namespace Symforce\CoreBundle\Command;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\PhpExecutableFinder;
use Symfony\Component\Process\ProcessBuilder;

class ServerRunCommand extends \Symfony\Bundle\FrameworkBundle\Command\ServerRunCommand
{

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $documentRoot = $input->getOption('docroot');
        if (null === $documentRoot) {
            $input->setOption('docroot', $this->getContainer()->getParameter('sf.web_root_dir') ) ;
        }
        return parent::execute($input, $output) ;
    }

}

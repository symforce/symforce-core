<?php

namespace Symforce\CoreBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Compiler\PassConfig;
use Symfony\Component\Console\Application;

use Symforce\CoreBundle\DependencyInjection\Compiler ;

class SymforceCoreBundle extends Bundle
{
    
    /**
     * {@inheritDoc}
     */
    public function registerCommands(Application $_application)
    {
        $_application->add(new Command\TestCommand());
        $_application->add(new Command\DumpCommand());
        $_application->add(new Command\AssetsCommand());
        $_application->add(new Command\ServerRunCommand());

        $_application->add(new Command\DoctrineCommandSchemaCreate());
        $_application->add(new Command\DoctrineCommandSchemaUpdate());
        $_application->add(new Command\DoctrineCommandSchemaDrop());
        $_application->add(new Command\DoctrineCommandMappingConvert());
        $_application->add(new Command\DoctrineCommandMappingImport());
    }

    public function build(ContainerBuilder $container)
    {
        parent::build($container);

        $container->addCompilerPass(new Compiler\AnnotationPass());
        $container->addCompilerPass(new Compiler\AssetsPass());
    }
    
}

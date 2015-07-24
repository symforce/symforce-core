<?php

namespace Symforce\CoreBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;

class PhpHelperPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container)
    {

        $compiler = new \Symforce\CoreBundle\Dev\Compiler\SymforceCoreDevCompiler() ;

        foreach ($container->findTaggedServiceIds($compiler::TAG_NAME) as $id => $attributes) {
            if (isset($attributes[0])) {
                $attributes = $attributes[0];
            }
            $compiler->addDevBuilder($id, $attributes);
        }
        $compiler->compile( $container->getParameter('kernel.root_dir')) ;

    }

}

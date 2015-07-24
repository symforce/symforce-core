<?php

namespace Symforce\CoreBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;

class EventPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container)
    {

        $compiler = new \Symforce\CoreBundle\Event\SymforceEventCompiler() ;

        foreach ($container->findTaggedServiceIds($compiler::TAG_NAME) as $id => $attributes) {
            if (isset($attributes[0])) {
                $attributes = $attributes[0];
            }

            $compiler->addEventBuilder($id, $attributes) ;
        }

        foreach ($container->findTaggedServiceIds($compiler::_TAG_NAME) as $id => $attributes) {
            if (isset($attributes[0])) {
                $attributes = $attributes[0];
            }

            $compiler->addEventArgumentBuilder($id, $attributes) ;
        }

        $compiler->compileEvents() ;
    }

}

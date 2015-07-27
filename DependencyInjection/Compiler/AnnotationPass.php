<?php

namespace Symforce\CoreBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;

class AnnotationPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container)
    {

        $compiler = new \Symforce\CoreBundle\Annotation\SymforceAnnotationCompiler();
        foreach ($container->findTaggedServiceIds($compiler::TYPE_TAG_NAME) as $id => $attributes) {
            if (isset($attributes[0])) {
                $attributes = $attributes[0];
            }
            $compiler->addAnnotationTypeCompiler($id, $attributes) ;
        }

        foreach ($container->findTaggedServiceIds($compiler::CLASS_TAG_NAME) as $id => $attributes) {
            if (isset($attributes[0])) {
                $attributes = $attributes[0];
            }
            $compiler->addAnnotationClassCompiler($id, $attributes) ;
        }

        foreach ($container->findTaggedServiceIds($compiler::PROPERTY_TAG_NAME) as $id => $attributes) {
            if (isset($attributes[0])) {
                $attributes = $attributes[0];
            }
            $compiler->addAnnotationPropertyCompiler($id, $attributes) ;
        }
        $compiler->compileAnnotations();
    }

}

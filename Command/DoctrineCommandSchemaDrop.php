<?php

namespace Symforce\CoreBundle\Command;

class DoctrineCommandSchemaDrop extends \Doctrine\Bundle\DoctrineBundle\Command\Proxy\DropSchemaDoctrineCommand
{
    use DoctrineCommandTraitSchema ;
}


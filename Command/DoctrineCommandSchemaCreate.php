<?php

namespace Symforce\CoreBundle\Command;

class DoctrineCommandSchemaCreate extends \Doctrine\Bundle\DoctrineBundle\Command\Proxy\CreateSchemaDoctrineCommand
{
    use DoctrineCommandTraitSchema ;
}


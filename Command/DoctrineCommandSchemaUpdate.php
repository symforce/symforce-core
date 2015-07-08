<?php

namespace Symforce\CoreBundle\Command;

class DoctrineCommandSchemaUpdate extends \Doctrine\Bundle\DoctrineBundle\Command\Proxy\UpdateSchemaDoctrineCommand
{
    use DoctrineCommandTraitSchema ;
}

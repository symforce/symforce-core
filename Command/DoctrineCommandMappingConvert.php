<?php

namespace Symforce\CoreBundle\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * ./app/console doctrine:mapping:convert --from-database --namespace="App\ForumBundle\Entity\\" annotation ./src
 * ./app/console doctrine:generate:entities  AppForumBundle
 */

class DoctrineCommandMappingConvert extends \Doctrine\Bundle\DoctrineBundle\Command\Proxy\ConvertMappingDoctrineCommand
{
    use DoctrineCommandTraitSchema ;

    /**
     * {@inheritDoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {

        if( $input->getOption('debug') ) {

            /**
             * @var $em \Doctrine\ORM\EntityManager
             */
            $em = $this->getApplication()->getKernel()->getContainer()->get('doctrine')->getManager( $input->getOption('em')) ;

            /**
             * @var $config \Doctrine\DBAL\Configuration
             */
            $config   = $em->getConnection()->getConfiguration();
            $ignore_tables    = $this->getApplication()->getKernel()->getContainer()->getParamer('sf.doctrine.ignore_tables') ;
            /**
             * @var $metadata \Doctrine\ORM\Mapping\ClassMetadata
             */
            foreach($em->getMetadataFactory()->getAllMetadata() as $metadata){
                $ignore_tables[] = $metadata->getTableName() ;
            }
            $schema_filter  = sprintf('~^(?!(%s))~', join('|', $ignore_tables )) ;
            $config->setFilterSchemaAssetsExpression($schema_filter) ;
        }

        return parent::execute($input, $output);
    }
}

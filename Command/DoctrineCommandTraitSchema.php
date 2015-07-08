<?php

namespace Symforce\CoreBundle\Command;

use
    Symfony\Component\Console\Input\InputOption,
    Symfony\Component\Console\Input\InputInterface,
    Symfony\Component\Console\Output\OutputInterface,
    Doctrine\ORM\Tools\SchemaTool;

trait DoctrineCommandTraitSchema {

    protected function configure()
    {
        parent::configure();
        $this->addOption('debug', null, InputOption::VALUE_NONE, 'to show debug version');
    }

    protected function executeSchemaCommand(InputInterface $input, OutputInterface $output, SchemaTool $schemaTool, array $metadatas)
    {
        $is_debug   = $input->getOption('debug')  ;

        if( $is_debug ) {
            if( $input->hasOption('force') ) {
                if( $input->getOption('force') ) {
                    $is_debug = false ;
                }
            } else {
                if( !$input->getOption('dump-sql') ) {
                    $is_debug = false ;
                }
            }

            if( $input->hasOption('full-database') ) {
                if( $input->getOption('full-database') ) {
                    throw new \Exception("can not use full-database") ;
                }
            }
        }

        /**
         * @var $config \Doctrine\DBAL\Configuration
         */
        $config   = $this->getHelper('db')->getConnection()->getConfiguration();
        $schema_filter  = $config->getFilterSchemaAssetsExpression() ;
        if( !$schema_filter ) {
            $is_debug   = true ;
        }

        if( $is_debug ) {
            $config->setFilterSchemaAssetsExpression(null) ;
            return parent::executeSchemaCommand($input, $output, $schemaTool, $metadatas) ;
        }


        /** @var $metadata \Doctrine\ORM\Mapping\ClassMetadata */
        $newMetadatas = array();
        foreach($metadatas as $metadata) {
            if( preg_match($schema_filter, $metadata->getTableName()) ){
                array_push($newMetadatas, $metadata);
            }
        }
        parent::executeSchemaCommand($input, $output, $schemaTool, $newMetadatas);
    }
} 
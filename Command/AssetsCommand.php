<?php

namespace Symforce\CoreBundle\Command ;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;

use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

use Symfony\Component\Filesystem\Filesystem ;

class AssetsCommand extends ContainerAwareCommand
{


    /**
     * {@inheritDoc}
     */
    protected function configure()
    {
        $this
            ->setName('sf:core:assets')
            ->setDescription('setup the admin caches')
            ->addOption('nosymlink', null, InputOption::VALUE_NONE, 'Symlinks the assets instead of copying it' )
            ->addOption('norelative', null, InputOption::VALUE_NONE, 'Make relative symlinks' )
            ->setHelp(<<<EOT

EOT
            );
    }

    /**
     * {@inheritDoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {

        if (!function_exists('symlink') && !$input->getOption('nosymlink') ) {
            throw new \InvalidArgumentException('The symlink() function is not available on your system. You need to install the assets without the --symlink option.');
        }
        $this->file_system  = new Filesystem();

        $assets_root_dir    =  $this->getContainer()->getParameter('sf.web_root_dir')  . $this->getContainer()->getParameter('sf.web_assets_dir')  ;
        $this->ensureDirectoryExists( $assets_root_dir ) ;
        $assets_root_dir    = realpath($assets_root_dir) ;

        $filesystem = $this->getContainer()->get('filesystem');
        $bundlesDir = $assets_root_dir .'/bundles/' ;
        $this->ensureDirectoryExists( $bundlesDir ) ;

        $output->writeln(sprintf('Installing assets as <comment>%s</comment>', !$input->getOption('nosymlink') ? 'symlinks' : 'hard copies'));

        foreach ($this->getContainer()->get('kernel')->getBundles() as $bundle) {
            if (is_dir($originDir = $bundle->getPath().'/Resources/public')) {
                $targetDir  = $bundlesDir.preg_replace('/bundle$/', '', strtolower($bundle->getName()));

                $output->writeln(sprintf('Installing assets for <comment>%s</comment> into <comment>%s</comment>', $bundle->getNamespace(), $targetDir));

                $filesystem->remove($targetDir);

                if ( !$input->getOption('nosymlink') ) {
                    if ( !$input->getOption('norelative') ) {
                        $relativeOriginDir = $filesystem->makePathRelative($originDir, realpath($bundlesDir));
                    } else {
                        $relativeOriginDir = $originDir;
                    }
                    $filesystem->symlink($relativeOriginDir, $targetDir);
                } else {
                    $filesystem->mkdir($targetDir, 0777);
                    // We use a custom iterator to ignore VCS files
                    $filesystem->mirror($originDir, $targetDir, Finder::create()->ignoreDotFiles(false)->in($originDir));
                }
            }
        }




        /**
         * @var $factory \Symforce\CoreBundle\Assets\SymforceAssetsFactory
         */
        $factory = $this->getContainer()->get('sf.assets.factory') ;


        $output->writeln(sprintf('Installing assets from <comment>%s</comment>', $factory->getTagName() ));


        $twig_parser  = $this->getContainer()->get('templating.name_parser');
        $twig_locator   = $this->getContainer()->get('templating.locator');
        $assets_target_dirs  =  $this->getContainer()->getParameter('sf.web_assets_target_dirs');

        /**
         * @var $assets_resource \Symforce\CoreBundle\Assets\SymforceAssetsResource
         */
        foreach($factory->getResources() as $service_id => $assets_resource ) {
            if( !in_array( $assets_resource->getTarget(), $assets_target_dirs) ) {
                throw new \Exception( sprintf("service(%s) with tags(name=%s, target=%s) target must be one of(%s)", $service_id, $factory->getTagName(), $assets_resource->getTarget(), join(',', $assets_target_dirs)));
            }
            $path = $assets_resource->getPath() ;

            try{
                $_path = $twig_locator->locate( $twig_parser->parse( $path ) ) ;
            } catch(\InvalidArgumentException $e) {
                throw new \Exception( sprintf("service(%s) with tags(name=%s, path=%s): %s", $service_id, $factory->getTagName(), $path, $e->getMessage() ));
            }
            $_target_dir = $assets_root_dir . '/' . $assets_resource->getTarget() ;
            $this->ensureDirectoryExists( $_target_dir ) ;


            $output->writeln(sprintf('Installing assets for <comment>%s</comment> into <comment>%s/%s</comment>', $path,   $this->getContainer()->getParameter('sf.web_assets_dir'), $assets_resource->getTarget() ));
            if( is_dir($_path) ) {
                $this->linkDir( $_path, $_target_dir , $assets_resource->getExtension() ) ;
            } else {
                $this->symlink( $_path , $_target_dir . '/' . basename( $_path ) );
            }
        }

        return 0 ;
    }

    /**
     * @var Filesystem
     */
    private $file_system  ;

    private function readlink($link) {
        $_target    = readlink($link) ;
        if(is_link( $_target) ) {
            return $this->readlink( $_target ) ;
        }
        return $_target ;
    }

    private function isDir( $dir ) {
        if( is_dir($dir) ) {
            return true ;
        }
        if( is_link($dir) ) {
            $_dir  = $this->readlink($dir) ;
            if( is_dir($_dir) ) {
                return true ;
            }
        }
        return false ;
    }

    private function getRealFileType( $file ) {
        if(is_link($file) ) {
            $file   = $this->readlink( $file ) ;
        }
        return filetype( $file ) ;
    }

    private function ensureDirectoryExists( $dir , $mode = 0755 ){
        $exists = null ;
        if( is_link($dir) ) {
            $exists = false ;
            if( !@unlink( $dir ) ) {
                throw new \Exception( sprintf("delete link file `%s` error: `%s`!",  $dir, json_encode( error_get_last() ) ) ) ;
            }
        }

        if( null === $exists ) {
            if( !file_exists( $dir) ) {
                $exists = false ;
            } else if( !is_dir($dir) ) {
                throw new \Exception( sprintf("expect dir, not `%`:`%s` !", filetype($dir), $dir ) ) ;
            } else {
                $exists = true ;
            }
        }

        if( !$exists ) {
            $_parents   = array( $dir ) ;
            for( $_dir = dirname( $dir) ; !file_exists( $_dir ) ;  $_dir = dirname( $_dir) ) {
                array_unshift($_parents, $_dir) ;
            }

            if( ! $this->isDir($_dir) ) {
                $_file_type = $this->getRealFileType($_dir) ;
                throw new \Exception( sprintf("expect dir, not `%s`:`%s` !",  $_file_type, $_dir ) ) ;
            }

            foreach($_parents as $_dir ) {
                if( is_link($_dir) ) {
                    $_real_dir  = $this->readlink( $_dir ) ;
                    throw new \Exception( sprintf("the link `%s` point to not exist target `%s`",  $_dir, $_real_dir ) ) ;
                }
                if( ! @mkdir( $_dir, $mode ) ) {
                    throw new \Exception( sprintf("mkdir `%s` error: `%s`!",  $_dir, json_encode( error_get_last() ) ) ) ;
                }
            }
        }
    }

    private function linkDir($from, $to , array $ext_list = null ) {
        if( !file_exists($from) ) {
            throw new \Exception( sprintf("`%s` is not exists!",  $from ) ) ;
        }

        for( $h = dir($from); $f = $h->read();  ) {
            if( $f == '.' || $f == '..' ) {
                continue;
            }
            $_form_path = $from . '/' . $f ;
            if( null !== $ext_list ) {
                if( is_file($_form_path) ) {
                    $_ext = strtolower( pathinfo($_form_path, PATHINFO_EXTENSION) );
                    if( !in_array($_ext, $ext_list) ) {
                        continue ;
                    }
                }
            }
            $this->symlink( $_form_path, $to . '/' . $f ) ;
        }
        $h->close() ;
    }

    private function symlink($target , $link ) {
        // echo __LINE__, "\n", $target, " ==> " , $link, "\n" ;
        if( !file_exists($target) ) {
            throw new \Exception( sprintf("`%s` is not exists", $target ) ) ;
        }
        if( is_link($link) ) {
            $_target    = readlink( $link ) ;
            if(realpath($_target) != realpath($target) ) {
                // throw new \Exception( sprintf("`%s` should be a link point to `%s`, but it point to `%s`",  $link , $target, $_target ) ) ;
                unlink($link) ;
            } else {
                return ;
            }
        } else if( file_exists($link)  ) {
            throw new \Exception( sprintf("`%s` should be a link point to `%s`",  $link , $target ) ) ;
        }
        $_path  = $this->file_system->makePathRelative( $target , dirname($link) ) ;
        $_path  = rtrim( $_path , '/' ) ;
        symlink($_path, $link) ;
        if( !file_exists($link) ) {
            throw new \Exception( sprintf("`%s` is not exists, link to `%s` -> `%s`", $link , $_path, $target ) ) ;
        }
    }
}

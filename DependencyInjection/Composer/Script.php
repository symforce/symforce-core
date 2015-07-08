<?php

namespace Symforce\CoreBundle\DependencyInjection\Composer;

use Composer\Script\Event;

class Script
{

    public static function hookRootPackageInstall(Event $event) {
        exec('./app/console sf:core:dump') ;
        return true;
    }
    
}
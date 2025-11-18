<?php

namespace WPTravelEngine\AddonStarter\Console;

use Symfony\Component\Console\Application as BaseApplication;
use WPTravelEngine\AddonStarter\Console\Commands\MakeAddonCommand;

class Application extends BaseApplication
{
    public function __construct()
    {
        parent::__construct('WP Travel Engine Addon Starter', '1.0.0');
        $this->add(new MakeAddonCommand());
    }
}

<?php

namespace Coff\W1MqttBroker\Application;

use Pimple\Container;
use Symfony\Component\Console\Application;

class W1MqttBrokerApplication extends Application
{
    protected $container;

    public function setContainer(Container $container) {
        $this->container = $container;

        return $this;
    }

    public function getContainer() {
        return $this->container;
    }
}
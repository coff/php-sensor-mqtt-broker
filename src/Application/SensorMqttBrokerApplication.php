<?php

namespace Coff\SensorMqttBroker\Application;

use Pimple\Container;
use Symfony\Component\Console\Application;

class SensorMqttBrokerApplication extends Application
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
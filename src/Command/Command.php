<?php

namespace Coff\W1MqttBroker\Command;


use Coff\W1MqttBroker\Application\W1MqttBrokerApplication;
use Pimple\Container;

/**
 * Command
 *
 * Basic Command with Pimple container attached.
 */
abstract class Command extends \Symfony\Component\Console\Command\Command
{
    protected $logFilename = 'w1-mqtt-broker.log';

    /**
     * Returns pimple DI container
     *
     * @return Container
     */
    public function getContainer() {
        /** @var W1MqttBrokerApplication $app */
        $app = $this->getApplication();

        return $app->getContainer();
    }

    public function getLogFilename() {
        return $this->logFilename;
    }
}

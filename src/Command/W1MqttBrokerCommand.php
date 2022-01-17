<?php

namespace Coff\SensorMqttBroker\Command;

use Coff\SensorMqttBroker\Broker\Broker;
use Pimple\Container;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class W1MqttBrokerCommand extends Command
{
    public function configure()
    {
        $this
            ->setName('broker:daemon')
            ->setDescription('Starts One-Wire sensors server (It\'s required to run main server)');
        ;
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        /** @var Container $container */
        $container = $this->getContainer();

        $container['running_command'] = $this;
        $container['interface:input'] = $input;

        /** @var Broker $server */
        $server = $container['broker:daemon'];

        $server->loop();
    }

}
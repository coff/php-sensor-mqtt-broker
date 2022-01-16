#!/usr/bin/php
<?php

namespace Coff\W1MqttBroker;

use Coff\W1MqttBroker\Application\W1MqttBrokerApplication;
use Coff\W1MqttBroker\Command\W1MqttBrokerCommand;
use Pimple\Container;

require __DIR__ . '/vendor/autoload.php';

$container = new Container();

require (__DIR__ . '/app/bootstrap.php');

$app = new W1MqttBrokerApplication('OneWire Broker', '0.0.1');
$app->setCatchExceptions(false);
$app->setContainer($container);

$app->add(new W1MqttBrokerCommand());

$app->run();



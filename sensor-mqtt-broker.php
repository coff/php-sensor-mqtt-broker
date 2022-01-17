#!/usr/bin/php
<?php

namespace Coff\SensorMqttBroker;

use Coff\SensorMqttBroker\Application\SensorMqttBrokerApplication;
use Coff\SensorMqttBroker\Command\W1MqttBrokerCommand;
use Pimple\Container;

require __DIR__ . '/vendor/autoload.php';

$container = new Container();

require (__DIR__ . '/app/bootstrap.php');

$app = new SensorMqttBrokerApplication('OneWire Broker', '0.0.1');
$app->setCatchExceptions(false);
$app->setContainer($container);

$app->add(new W1MqttBrokerCommand());

$app->run();



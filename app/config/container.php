<?php


use Coff\DataSource\W1\W1FileDataSource;
use Coff\Max6675\Max6675DataSource;
use Coff\W1MqttBroker\Command\Command;
use Coff\W1MqttBroker\Sensor\DS18B20Sensor;
use Coff\W1MqttBroker\Sensor\Max6675Sensor;
use Symfony\Component\Console\Formatter\OutputFormatter;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Logger\ConsoleLogger;
use Symfony\Component\Console\Output\StreamOutput;
use Volantus\Pigpio\Client;
use Volantus\Pigpio\Network\Socket;
use Volantus\Pigpio\SPI\RegularSpiDevice;

$container['mqtt:clientId'] = function ($c) {
    return 'w1-mqtt-broker';
};

$container['w1_system:path'] = function ($c) {
    return '/sys/devices/w1_bus_master1';
};

$container['client:pigpio'] = function($c) {
    return new Client(new Socket('127.0.0.1', 8888));
};

$container['sensors:topics'] = function ($c) {
    return [
        '28-00000891595f' => 'home/groundfloor',
        '28-0000088fc71c' => 'home/heating/buffer/top',
        '28-0000084b947a' => 'home/heating/buffer/bottom',
        '28-051685dc73ff' => 'home/heating/radiator/powerline',
        '28-0316848610ff' => 'home/heating/radiator/return',
        '28-0416747d17ff' => 'home/heating/boiler/top',
        '28-0000084a49a8' => 'home/heating/boiler/bottom'
    ];
};

$container['sensors'] = function ($c) {
    DS18B20Sensor::$defaultMqttTimetampSuffix = 'timestamp';
    DS18B20Sensor::$defaultMqttValueSuffix = 'temp';
    Max6675Sensor::$defaultMqttTimetampSuffix = 'timestamp';
    Max6675Sensor::$defaultMqttValueSuffix = 'temp';

    $path = $c['w1_system:path'];
    $sensors = [];

    foreach ($c['sensors:topics'] as $dsId => $topic) {
        $dataSource = new W1FileDataSource($dsId, $path);
        $sensors[$dsId] = new DS18B20Sensor($dataSource);
        $sensors[$dsId]->setMqttBaseTopic($topic);
    }

    $dataSource = new Max6675DataSource();
    $device = new RegularSpiDevice($c['client:pigpio'], 1, 32000);
    $device->open();
    $dataSource->setSpiDevice($device);
    $sensors['thermocouple1'] = new Max6675Sensor($dataSource);
    $sensors['thermocouple1']->setMqttBaseTopic('home/heating/boiler/exhaust');

    return $sensors;
};

$container['logger'] = function ($c) {

    /** @var Command $command  */
    $command = $c['running_command'];

    /* each command should tell us its logfile name */
    $res = fopen('../' . $command->getLogFilename(), 'a');
    $output = new StreamOutput($res, StreamOutput::VERBOSITY_NORMAL, $isDecorated=true, new OutputFormatter());
    $logger = new ConsoleLogger($output);
    $logger->info('Logger initialized');
    return $logger;
};

$container['broker:daemon'] = function($c) {

    /** @var InputInterface $input */
    $input = $c['interface:input'];

    $server = new Coff\W1MqttBroker\Broker\Broker();

    $server
        ->setLogger($c['logger'])
        ->setW1Path($c['w1_system:path'])
        ->setMqttClient($c['mqtt:client'])
        ->setSensors($c['sensors'])
        ->setTempStampSignature('home/w1-mqtt-broker/lastupdate')
        ->init();
    return $server;
};

$container['mqtt:client'] = function ($c) {
    return new \PhpMqtt\Client\MqttClient(
        'localhost',
        1883,
        $c['mqtt:clientId'],
        \PhpMqtt\Client\MqttClient::MQTT_3_1,
        new \PhpMqtt\Client\Repositories\MemoryRepository(),
        $c['logger']
    );
};
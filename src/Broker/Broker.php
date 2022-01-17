<?php

namespace Coff\W1MqttBroker\Broker;


use Coff\DataSource\Exception\DataSourceException;
use Coff\DataSource\W1\W1DataSource;
use Coff\DataSource\W1\W1FileDataSource;
use Coff\Max6675\Max6675DataSource;
use Coff\Sensor\MqttSensor;
use Coff\Ticker\CallableTick;
use Coff\Ticker\Ticker;
use Coff\W1MqttBroker\Sensor\UnknownW1Sensor;
use PhpMqtt\Client\Exceptions\MqttClientException;
use PhpMqtt\Client\MqttClient;
use Psr\Log\LogLevel;

class Broker
{
    protected $logger;
    protected $ticker;
    protected $sleepTime=100000;
    protected $w1Path = '/sys/devices/w1_bus_master1';
    protected $unknownDeviceTopic = "home/unknown";
    protected $tempStampSignature;

    /**
     * @var MqttClient
     */
    protected $mqttClient;

    /**
     * @var MqttSensor[] $sensors
     */
    protected $sensors;

    /**
     * @var resource[] $dataSourceStreams
     */
    protected $dataSourceStreams;

    public function init()
    {
        $this->ticker = new Ticker();

        $this->ticker->addTick(new CallableTick(Ticker::SECOND, 10, [$this, 'queryDevices']));
        $this->ticker->addTick(new CallableTick(Ticker::SECOND, 1, [$this, 'readReadings']));
        $this->ticker->addTick(new CallableTick(Ticker::SECOND, 10, [$this, 'sendReadings']));
        $this->ticker->addTick(new CallableTick(Ticker::MINUTE, 1, [$this, 'devicesDiscovery']));

        // initial devices discovery
        $this->devicesDiscovery();

        return $this;
    }

    public function setLogger($logger)
    {
        $this->logger = $logger;
        return $this;
    }

    public function setW1Path($path)
    {
        $this->w1Path = $path;
        return $this;
    }

    public function setSensors(array $sensors) {
        $this->sensors = $sensors;
        return $this;
    }

    public function setTempStampSignature($signature) {
        $this->tempStampSignature = $signature;
        return $this;
    }

    public function setMqttClient($client)
    {
        $this->mqttClient = $client;
        return $this;
    }

    public function devicesDiscovery() {

        $dir = new \DirectoryIterator($this->w1Path);

        /* reset streams in case there were some unfinished queries */
        $this->dataSourceStreams = array();

        foreach($dir as $fileInfo) {
            try {
                if ($fileInfo->isDot()) {
                    continue;
                }

                if (false === file_exists($dataSourcePath = $fileInfo->getPathname() . '/w1_slave')) {
                    continue;
                }

                if (false === isset($this->sensors[$fileInfo->getFilename()])) {
                    $this->sensors[$fileInfo->getFilename()] = $sensor = new UnknownW1Sensor(new W1FileDataSource($fileInfo->getFilename(), $this->w1Path));
                    $sensor->setMqttBaseTopic($this->unknownDeviceTopic . '/' . $fileInfo->getFilename());
                }

            } catch (DataSourceException $e) {
                $this->logger->log(LogLevel::WARNING, 'Device discovery failed for ' . $fileInfo->getPathname() );
            }
        }

        $this->logger->info('Finished 1-Wire device discovery', array_keys($this->sensors));

        $this->lastDiscoveryTime = time();
    }

    public function queryDevices() {

        if (empty($this->sensors)) {
            $this->logger->log(LogLevel::NOTICE, 'No datasources defined');
            return;
        }

        $this->dataSourceStreams = array();

        /**
         * @var string $key
         */
        foreach ($this->sensors as $key => $sensor) {

            try {
                if ($sensor->getDataSource() instanceof W1FileDataSource) {
                    $stream = $sensor
                        ->getDataSource()
                        ->request()
                        ->getStream();

                    $this->dataSourceStreams[$key] = $stream;
                } elseif ($sensor->getDataSource() instanceof Max6675DataSource) {
                    $sensor->update();
                }

            } catch (DataSourceException $e) {
                $this->logger->log(LogLevel::ALERT, $e->getMessage(), $e->getCode());
            }
        }
        $this->logger->info('Initialized data source queries', array_keys($this->sensors));

        $this->lastQueryTime = time();
    }

    public function readReadings()
    {
        $streams = $this->dataSourceStreams; $w=null; $o=null;

        if ($this->dataSourceStreams && 0 < stream_select($streams, $w, $o, 0, $this->sleepTime)) {

            foreach ($this->dataSourceStreams as $key => $stream) {
                $sensor = $this->sensors[$key];
                $sensor
                    ->update();

                $this->logger->info('Got answer from ' . $key, [$sensor->getValue()]);

                if (false === is_resource($stream)) {
                    unset($this->dataSourceStreams[$key]);

                    if (!$this->dataSourceStreams) {
                        $this->logger->info('Collecting answers completed');
                        $this->allCollected = true;
                    }
                }

            }

        }
    }

    public function sendReadings()
    {
        $stamp = date('Y-m-d H:i:s');

        $this->logger->info('Connecting MQTT instance to send data...');

        try {
            $this->mqttClient->connect();

            /**
             * @var Coff\Sensor\MqttSensor
             */
            foreach ($this->sensors as $key => $sensor) {
                $this->logger->debug('Publishing MQTT sensor data for '. $sensor->getMqttBaseTopic());
                $this->mqttClient->publish($sensor->getMqttValueTopic(), $sensor->getValue());
                $this->mqttClient->publish($sensor->getMqttTimestampTopic(), $stamp);
            }

            if ($this->tempStampSignature) {
                $this->mqttClient->publish($this->tempStampSignature, $stamp);
            }

            $this->mqttClient->disconnect();
        } catch (MqttClientException $e) {
            $this->logger->alert('Mqtt broker connection failed');

            // try another time
            return;
        }
    }

    public function loop()
    {
        $this->ticker->loop();

        return $this;
    }
}
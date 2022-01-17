<?php

namespace Coff\SensorMqttBroker\Sensor;

use Coff\DataSource\DataSource;
use Coff\Sensor\MqttSensor;

/**
 * DS18B20Sensor
 *
 * A DS18B20 chip readings handling class.
 */
class DS18B20Sensor extends MqttSensor
{

    /** @var  DataSource */
    protected $dataSource;
    protected $resource;

    /**
     * TemperatureSensor constructor.
     *
     * @param DataSource $dataSource
     * @param string $measureUnit
     */
    public function __construct(DataSource $dataSource, $measureUnit = 'celsius') {
        parent::__construct();

        $this->dataSource = $dataSource;
        $this->measureUnit = $measureUnit;
    }

    /**
     * Sensor init.
     *
     * @return $this
     */
    public function init()
    {
        return $this;
    }

    protected function parseReading($reading) {
        $lines = explode("\n", $reading);

        if (count($lines) < 2) {
            return;
        }

        $crcLine = trim($lines[0]);
        $valueLine = trim($lines[1]);
        if (substr($crcLine,-1) == 'S') { // YE(S)
            $this->value = (double)explode('=', substr($valueLine,-8))[1] / 1000;
        }
    }

    /**
     * Updates value reading according to DataSource data.
     *
     * @return $this
     */
    public function update() {
        $this->parseReading($this->dataSource->update()->getValue());

        return $this;
    }
}

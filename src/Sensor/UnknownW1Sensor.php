<?php

namespace Coff\W1MqttBroker\Sensor;

use Coff\DataSource\DataSourceInterface;
use Coff\DataSource\W1\W1FileDataSource;
use Coff\Sensor\MqttSensor;

class UnknownW1Sensor extends MqttSensor
{

    public function __construct(W1FileDataSource $dataSource)
    {
        parent::__construct();

        $this->dataSource = $dataSource;
        $this->setMqttValueSuffix('rawValue');
    }

    public function init()
    {
        return $this;
    }

    public function update()
    {
        $this->value = $this->dataSource->update()->getValue();

        return $this;
    }
}
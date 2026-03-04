<?php
class ServiceDriverFactory
{
    public static function make(array $service): ServiceDriverInterface
    {
        return $service['protocol'] === 'soap'
            ? new SoapDriver()
            : new HttpDriver();
    }
}

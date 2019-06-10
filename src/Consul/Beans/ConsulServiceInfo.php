<?php
/**
 * Created by PhpStorm.
 * User: 白猫
 * Date: 2019/4/26
 * Time: 16:51
 */

namespace ESD\Plugins\Consul\Beans;


use ESD\Psr\Cloud\ServiceInfo;

class ConsulServiceInfo extends ServiceInfo
{
    public function __construct($serviceName, $serviceId, $serviceAddress, $servicePort, $serviceMeta, $serviceTags)
    {
        $serviceAgreement = $serviceMeta['agreement'];
        parent::__construct($serviceName, $serviceId, $serviceAddress, $servicePort, $serviceMeta, $serviceTags, $serviceAgreement);
    }
}
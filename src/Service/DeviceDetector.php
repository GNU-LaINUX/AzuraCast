<?php

declare(strict_types=1);

namespace App\Service;

use App\Service\DeviceDetector\DeviceResult;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\Cache\Adapter\ChainAdapter;
use Symfony\Component\Cache\Adapter\ProxyAdapter;
use Symfony\Component\Cache\CacheItem;
use Symfony\Contracts\Cache\CacheInterface;

class DeviceDetector
{
    protected CacheInterface $cache;

    protected \DeviceDetector\DeviceDetector $dd;

    public function __construct(
        CacheItemPoolInterface $psr6Cache
    ) {
        $this->cache = new ProxyAdapter(
            new ChainAdapter([
                new ArrayAdapter(),
                $psr6Cache,
            ]),
            'device_detector.'
        );

        $this->dd = new \DeviceDetector\DeviceDetector();
    }

    public function parse(string $userAgent): DeviceResult
    {
        $userAgentHash = md5($userAgent);

        return $this->cache->get(
            $userAgentHash,
            function (CacheItem $item) use ($userAgent) {
                /** @noinspection SummerTimeUnsafeTimeManipulationInspection */
                $item->expiresAfter(86400 * 7);

                $this->dd->setUserAgent($userAgent);
                $this->dd->parse();

                return DeviceResult::fromDeviceDetector($this->dd);
            }
        );
    }
}

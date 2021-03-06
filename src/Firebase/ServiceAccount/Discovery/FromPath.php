<?php

namespace Kreait\Firebase\ServiceAccount\Discovery;

use Kreait\Firebase\Exception\ServiceAccountDiscoveryFailed;
use Kreait\Firebase\ServiceAccount;
class FromPath
{
    /**
     * @var string
     */
    private $path;
    public function __construct($path)
    {
        $this->path = $path;
    }
    public function __invoke()
    {
        try {
            return ServiceAccount::fromValue($this->path);
        } catch (\Throwable $e) {
            throw new ServiceAccountDiscoveryFailed($e->getMessage());
        }
    }
}
<?php

namespace Kreait\Firebase\ServiceAccount\Discovery;

use Kreait\Firebase\Exception\ServiceAccountDiscoveryFailed;
use Kreait\Firebase\ServiceAccount;
class FromEnvironmentVariable
{
    /**
     * @var string
     */
    private $value;
    public function __construct($value)
    {
        $this->value = $value;
    }
    public function __invoke()
    {
        $msg = sprintf('%s: The environment variable "%s"', static::class, $this->value);
        if (!($path = getenv($this->value))) {
            throw new ServiceAccountDiscoveryFailed(sprintf('%s is not set.', $msg));
        }
        $msg .= sprintf(' points to "%s"', $path);
        try {
            return (new FromPath($path))();
        } catch (ServiceAccountDiscoveryFailed $e) {
            throw new ServiceAccountDiscoveryFailed(sprintf('%s, but has errors: %s', $msg, $e->getMessage()));
        }
    }
}
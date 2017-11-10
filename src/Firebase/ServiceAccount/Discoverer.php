<?php

namespace Kreait\Firebase\ServiceAccount;

use Google\Auth\CredentialsLoader;
use Kreait\Firebase\Exception\ServiceAccountDiscoveryFailed;
use Kreait\Firebase\ServiceAccount;
class Discoverer
{
    /**
     * @var callable[]
     */
    private $methods;
    /**
     * @param callable[] $methods
     */
    public function __construct(array $methods = [])
    {
        $this->methods = $methods ?: $this->getDefaultMethods();
    }
    public function getDefaultMethods()
    {
        return [new Discovery\FromEnvironmentVariable('FIREBASE_CREDENTIALS'), new Discovery\FromEnvironmentVariable(CredentialsLoader::ENV_VAR), new Discovery\FromGoogleWellKnownFile()];
    }
    public function discover()
    {
        $messages = [];
        $serviceAccount = array_reduce($this->methods, function ($discovered, callable $method) use(&$messages) {
            try {
                $discovered = isset($discovered) ? $discovered : $method();
            } catch (\Throwable $e) {
                $messages[] = $e->getMessage();
            }
            return $discovered;
        });
        if (!$serviceAccount instanceof ServiceAccount) {
            throw new ServiceAccountDiscoveryFailed(implode(PHP_EOL, $messages));
        }
        return $serviceAccount;
    }
}
<?php

namespace Kreait\Firebase;

use Firebase\Auth\Token\Handler as TokenHandler;
use GuzzleHttp\Client;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7;
use Kreait\Firebase;
use Kreait\Firebase\Auth\CustomTokenGenerator;
use Kreait\Firebase\Auth\IdTokenVerifier;
use Kreait\Firebase\Http\Middleware;
use Kreait\Firebase\ServiceAccount\Discoverer;
use Psr\Http\Message\UriInterface;
class Factory
{
    /**
     * @var UriInterface
     */
    private $databaseUri;
    /**
     * @var TokenHandler
     */
    private $tokenHandler;
    /**
     * @var ServiceAccount
     */
    private $serviceAccount;
    /**
     * @var Discoverer
     */
    private $serviceAccountDiscoverer;
    /**
     * @var string|null
     */
    private $apiKey;
    private static $databaseUriPattern = 'https://%s.firebaseio.com';
    /**
     * @deprecated 3.1 use {@see withServiceAccount()} instead
     *
     * @param string $credentials Path to a credentials file
     *
     * @throws \Kreait\Firebase\Exception\InvalidArgumentException
     *
     * @return self
     */
    public function withCredentials($credentials)
    {
        trigger_error('This method is deprecated and will be removed in release 4.0 of this library.' . ' Use Firebase\\Factory::withServiceAccount() instead.', E_USER_DEPRECATED);
        return $this->withServiceAccount(ServiceAccount::fromValue($credentials));
    }
    public function withApiKey($apiKey)
    {
        $factory = clone $this;
        $factory->apiKey = $apiKey;
        return $factory;
    }
    public function withServiceAccount(ServiceAccount $serviceAccount)
    {
        $factory = clone $this;
        $factory->serviceAccount = $serviceAccount;
        return $factory;
    }
    public function withServiceAccountAndApiKey(ServiceAccount $serviceAccount, $apiKey)
    {
        $factory = clone $this;
        $factory->serviceAccount = $serviceAccount;
        $factory->apiKey = $apiKey;
        return $factory;
    }
    public function withServiceAccountDiscoverer(Discoverer $discoverer)
    {
        $factory = clone $this;
        $factory->serviceAccountDiscoverer = $discoverer;
        return $factory;
    }
    public function withDatabaseUri($uri)
    {
        $factory = clone $this;
        $factory->databaseUri = Psr7\uri_for($uri);
        return $factory;
    }
    /**
     * @deprecated 3.2 Use `Kreait\Firebase\Auth::createCustomToken()` and `Kreait\Firebase\Auth::verifyIdToken()` instead.
     *
     * @param TokenHandler $handler
     *
     * @return Factory
     */
    public function withTokenHandler(TokenHandler $handler)
    {
        trigger_error('The token handler is deprecated and will be removed in release 4.0 of this library.' . ' Use Firebase\\Auth::createCustomToken() or Firebase\\Auth::verifyIdToken() instead.', E_USER_DEPRECATED);
        $factory = clone $this;
        $factory->tokenHandler = $handler;
        return $factory;
    }
    public function create()
    {
        $serviceAccount = isset($this->serviceAccount) ? $this->serviceAccount : $this->getServiceAccountDiscoverer()->discover();
        $databaseUri = isset($this->databaseUri) ? $this->databaseUri : $this->getDatabaseUriFromServiceAccount($serviceAccount);
        $tokenHandler = isset($this->tokenHandler) ? $this->tokenHandler : $this->getDefaultTokenHandler($serviceAccount);
        $tokenGenerator = new CustomTokenGenerator($serviceAccount);
        $idTokenVerifier = new IdTokenVerifier($serviceAccount);
        $auth = $this->apiKey ? $this->createAuth($this->apiKey, $tokenGenerator, $idTokenVerifier) : null;
        return new Firebase($serviceAccount, $databaseUri, $tokenHandler, $auth);
    }
    private function getServiceAccountDiscoverer()
    {
        return isset($this->serviceAccountDiscoverer) ? $this->serviceAccountDiscoverer : new Discoverer();
    }
    private function getDatabaseUriFromServiceAccount(ServiceAccount $serviceAccount)
    {
        return Psr7\uri_for(sprintf(self::$databaseUriPattern, $serviceAccount->getProjectId()));
    }
    private function getDefaultTokenHandler(ServiceAccount $serviceAccount)
    {
        return new TokenHandler($serviceAccount->getProjectId(), $serviceAccount->getClientEmail(), $serviceAccount->getPrivateKey());
    }
    private function createAuth($apiKey, CustomTokenGenerator $customTokenGenerator, IdTokenVerifier $idTokenVerifier)
    {
        $client = $this->createAuthApiClient($apiKey);
        return new Auth($client, $customTokenGenerator, $idTokenVerifier);
    }
    private function createAuthApiClient($apiKey)
    {
        $stack = HandlerStack::create();
        $stack->push(Middleware::ensureApiKey($apiKey), 'ensure_api_key');
        $httpClient = new Client(['base_uri' => 'https://www.googleapis.com/identitytoolkit/v3/relyingparty/', 'handler' => $stack]);
        return new Firebase\Auth\ApiClient($httpClient);
    }
}
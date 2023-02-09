<?php

namespace PayPalCheckoutSdk\Support;

use PayPalCheckoutSdk\Core\PayPalHttpClient;
use PayPalCheckoutSdk\Core\AccessTokenRequest;
use PayPalCheckoutSdk\Core\ClientTokenRequest;
use PayPalCheckoutSdk\Core\SandboxEnvironment;
use PayPalCheckoutSdk\Core\ProductionEnvironment;

class Authentication
{

    protected $clientId;
    
    protected $clientSecret;
    
    protected $environment;

    protected $client;

    /**
     * @var AccessTokenObject
     */
    protected $accessTokenObject;

    protected $cacheFilePath;


    /**
     * Store credentials internally.
     */
    public function __construct($clientId, $clientSecret, $cacheFilePath, $environmentType = 'sandbox')
    {
        $this->clientId = $clientId;
        $this->clientSecret = $clientSecret;
        $this->environment = $environmentType;
        $this->cacheFilePath = $cacheFilePath;

        if($environmentType == 'sandbox') {
            $this->environment = new SandboxEnvironment($this->clientId, $this->clientSecret);
        } else {
            $this->environment = new ProductionEnvironment($this->clientId, $this->clientSecret);
        }
    }


    /**
     * Instantiate the client
     */
    public function getClient()
    {
        if($this->client) {
            return $this->client;
        }

        return $this->client = new PayPalHttpClient($this->environment);
    }


    /**
     * Gets an access token
     */
    public function getAccessToken()
    {
        // try to pull access token from cache
        if($accessToken = $this->pullFromCache()) {
            return $accessToken;
        }
    
       /**
        * Get an access token.
        * It fails with exception is something goes wrong.
        */
       $request = new AccessTokenRequest($this->environment);
       $response = $this->getClient()->execute($request);
       
       // store access token in cache
       $this->cacheToken($response->result);
       
       return $response->result->access_token;
    }

    
    /**
     * Store access token in cache
     */
    protected function cacheToken($sourceObject)
    {
        file_put_contents($this->cacheFilePath, json_encode([
            'sourceObject' => $sourceObject,
            'createdAt' => time(),
        ]));
    }   

    /**
     * Pull access token from cache
     */
    protected function pullFromCache()
    {

        if(!file_exists($this->cacheFilePath)) {
            return null;
        }
        $data = json_decode(file_get_contents($this->cacheFilePath), true);
        
        // if no valid data is found or token is expired
        if(!$data || time() >= $data['createdAt'] + $data['sourceObject']['expires_in']) {
            return null;
        }

        return $data['sourceObject']['access_token'];
    }


    /**
     * Gets a client token
     */
    public function getClientToken($accessToken)
    {

        $clientTokenRequest = new ClientTokenRequest($accessToken);
        $response = $this->getClient()->execute($clientTokenRequest);
        $clientToken = $response->result->client_token;

        return $clientToken;

    }

}

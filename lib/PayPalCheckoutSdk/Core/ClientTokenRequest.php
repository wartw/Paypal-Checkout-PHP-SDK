<?php

namespace PayPalCheckoutSdk\Core;

use PayPalHttp\HttpRequest;

class ClientTokenRequest extends HttpRequest
{
    public function __construct($accessToken)
    {
        parent::__construct("/v1/identity/generate-token", "POST");
        $this->headers["Authorization"] = "Bearer " . $accessToken;
        $body = [
            "grant_type" => "client_credentials"
        ];

        $this->body = $body;
        $this->headers["Content-Type"] = "application/json";
    }
}


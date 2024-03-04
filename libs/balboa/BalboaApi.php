<?php

class BalboaApi
{

    const ROUTE_LOGIN       = 'users/login';
    const ROUTE_DEVICES_SCI = 'devices/sci';

    /**
     * @var string
     */
    private $baseUrl = 'https://bwgapi.balboawater.com';

    /**
     * @var string
     */
    private $username;

    /**
     * @var string
     */
    private $password;

    /**
     * @var string|null
     */
    private $bearerToken;

    /**
     * @var string|null
     */
    private $deviceId;

    /**
     * @var BalboaApiRequest|null
     */
    private $lastRequest;

    /**
     * @var BalboaApiResponse|null
     */
    private $lastResponse;

    /**
     * @param string $username
     * @param string $password
     */
    public function __construct(string $username, string $password)
    {
        $this->username = $username;
        $this->password = $password;
    }

    /**
     * @return string
     */
    public function getBaseUrl(): string
    {
        return $this->baseUrl;
    }

    /**
     * @return string
     */
    public function getUsername(): string
    {
        return $this->username;
    }

    /**
     * @return string
     */
    public function getPassword(): string
    {
        return $this->password;
    }

    /**
     * @return string|null
     */
    public function getBearerToken(): ?string
    {
        return $this->bearerToken;
    }

    /**
     * @param string|null $bearerToken
     */
    public function setBearerToken(?string $bearerToken): void
    {
        $this->bearerToken = $bearerToken;
    }

    /**
     * @return string|null
     */
    public function getDeviceId(): ?string
    {
        return $this->deviceId;
    }

    /**
     * @param string|null $deviceId
     */
    public function setDeviceId(?string $deviceId): void
    {
        $this->deviceId = $deviceId;
    }

    /**
     * @return BalboaApiRequest|null
     */
    public function getLastRequest(): ?BalboaApiRequest
    {
        return $this->lastRequest;
    }

    /**
     * @return BalboaApiResponse|null
     */
    public function getLastResponse(): ?BalboaApiResponse
    {
        return $this->lastResponse;
    }

    /**
     * @return bool returns true if the authentication was successful
     */
    public function authenticate(): bool
    {
        $postFields = json_encode([ 'username' => $this->username, 'password' => $this->password ]);
        $headers    = [ 'Content-Type: application/json' ];
        $response   = $this->request(self::ROUTE_LOGIN, $postFields, $headers);
        $result     = json_decode($response->getBody(), true);

        if ($response->hasError() || !isset($result['token'], $result['device']['device_id'])) {
            return false;
        }

        $this->setBearerToken($result['token']);
        $this->setDeviceId($result['device']['device_id']);

        return true;
    }

    /**
     * @param string $xml
     *
     * @return BalboaApiResponse
     */
    public function devicesSci(string $xml): BalboaApiResponse
    {
        $headers  = [ 'Content-Type: application/xml' ];
        $response = $this->request(self::ROUTE_DEVICES_SCI, $xml, $headers);

        return $response;
    }

    /**
     * @param string $path
     * @param string $postFields
     * @param array  $headers
     *
     * @return BalboaApiResponse
     */
    private function request(string $path, string $postFields, array $headers = []): BalboaApiResponse
    {
        $url  = sprintf('%s/%s', $this->baseUrl, $path);
        $ch   = curl_init();
        $post = true;

        $headers     = array_merge([
            'User-Agent: BWA/4.1 (com.createch-group.balboa; build:10; iOS 13.3.0) Alamofire/4.8.1',
            'Accept-Language: nl-NL;q=1.0',
            'Content-Length: ' . strlen($postFields),
        ], $headers);
        $bearerToken = $this->getBearerToken();
        if (!empty($bearerToken)) {
            $headers[] = 'Authorization: ' . $bearerToken;
        }

        $request           = new BalboaApiRequest();
        $this->lastRequest = $request;

        $request->setUrl($url);
        $request->setHeaders($headers);
        $request->setPost(true);
        $request->setPostData($postFields);

        $timeStart = microtime(true);

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, $post ? 1 : 0);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postFields);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        $response           = new BalboaApiResponse();
        $this->lastResponse = $response;
        $responseData       = curl_exec($ch);

        $response->setUrl($url);
        $response->setHttpCode(curl_getinfo($ch, CURLINFO_HTTP_CODE));
        if (curl_errno($ch)) {
            $response->setError(curl_error($ch));
        } else {
            $response->setBody($responseData);
        }
        curl_close($ch);
        $duration = round(microtime(true) - $timeStart, 2);

        $response->setDuration($duration);

        return $response;
    }

}
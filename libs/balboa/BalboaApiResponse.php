<?php

class BalboaApiResponse
{

    /**
     * @var string
     */
    private $url;

    /**
     * @var int
     */
    private $httpCode;

    /**
     * @var string
     */
    private $body;

    /**
     * @var string
     */
    private $error;

    /**
     * @var float
     */
    private $duration;

    public function __construct()
    {
        $this->setHttpCode(0);
        $this->setUrl('');
        $this->setBody('');
        $this->setError('');
        $this->setDuration(0.0);
    }

    /**
     * @return string
     */
    public function getUrl(): string
    {
        return $this->url;
    }

    /**
     * @param string $url
     */
    public function setUrl(string $url): void
    {
        $this->url = $url;
    }

    /**
     * @return int
     */
    public function getHttpCode(): int
    {
        return $this->httpCode;
    }

    /**
     * @param int $httpCode
     */
    public function setHttpCode(int $httpCode): void
    {
        $this->httpCode = $httpCode;
    }

    /**
     * @return string
     */
    public function getBody(): string
    {
        return $this->body;
    }

    /**
     * @param string $body
     */
    public function setBody(string $body): void
    {
        $this->body = $body;
    }

    /**
     * @return bool
     */
    public function hasError(): bool
    {
        return !empty($this->getError());
    }

    /**
     * @return string
     */
    public function getError(): string
    {
        return $this->error;
    }

    /**
     * @param string $error
     */
    public function setError(string $error): void
    {
        $this->error = $error;
    }

    /**
     * @return float
     */
    public function getDuration(): float
    {
        return $this->duration;
    }

    /**
     * @param float $duration
     */
    public function setDuration(float $duration): void
    {
        $this->duration = $duration;
    }

}
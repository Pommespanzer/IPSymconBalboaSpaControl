<?php

class BalboaApiRequest
{

    /**
     * @var string
     */
    private $url = '';

    /**
     * @var array
     */
    private $headers = [];

    /**
     * @var bool
     */
    private $post = false;

    /**
     * @var string
     */
    private $postData = '';

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
     * @return array
     */
    public function getHeaders(): array
    {
        return $this->headers;
    }

    /**
     * @param array $headers
     */
    public function setHeaders(array $headers): void
    {
        $this->headers = $headers;
    }

    /**
     * @return bool
     */
    public function isPost(): bool
    {
        return $this->post;
    }

    /**
     * @param bool $post
     */
    public function setPost(bool $post): void
    {
        $this->post = $post;
    }

    /**
     * @return string
     */
    public function getPostData(): string
    {
        return $this->postData;
    }

    /**
     * @param string $postData
     */
    public function setPostData(string $postData): void
    {
        $this->postData = $postData;
    }

}
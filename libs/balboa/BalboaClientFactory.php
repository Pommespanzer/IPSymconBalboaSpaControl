<?php

class BalboaClientFactory
{

    /**
     * @param string $username
     * @param string $password
     *
     * @return BalboaClient
     */
    public static final function createClient(string $username, string $password): BalboaClient
    {
        $api    = new BalboaApi($username, $password);
        $client = new BalboaClient($api);

        return $client;
    }

}
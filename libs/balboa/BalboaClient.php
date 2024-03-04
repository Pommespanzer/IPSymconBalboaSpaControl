<?php

class BalboaClient
{

    const ACTION_ON  = 'on';  // used for buttons
    const ACTION_OFF = 'off'; // used for buttons

    const TARGET_BUTTON   = 'Button';
    const TARGET_SET_TEMP = 'SetTemp';

    // Pump buttons
    const BUTTON_4 = 4;
    const BUTTON_5 = 5;
    const BUTTON_6 = 6;
    const BUTTON_7 = 7;
    const BUTTON_8 = 8;
    const BUTTON_9 = 9;

    // Blower buttons
    const BUTTON_12 = 12;

    // Mister buttons
    const BUTTON_14 = 14;

    // Light buttons
    const BUTTON_17 = 17;
    const BUTTON_18 = 18;

    // AUX
    const BUTTON_22 = 22;
    const BUTTON_23 = 23;

    // Temp/Heat
    const BUTTON_80 = 80;     // Temp
    const BUTTON_81 = 81;     // HeatMode

    const PUMP_BUTTON_MAP = [
        1 => self::BUTTON_4, // Pump #1 maps to BUTTON_4
        2 => self::BUTTON_5, // Pump #2 maps to BUTTON_5
        3 => self::BUTTON_6, // Pump #3 maps to BUTTON_6
        4 => self::BUTTON_7, // Pump #4 maps to BUTTON_7
        5 => self::BUTTON_8, // Pump #5 maps to BUTTON_8
        6 => self::BUTTON_9, // Pump #6 maps to BUTTON_9
    ];

    // TODO map needed? there is only one blower
    const BLOWER_BUTTON_MAP = [
        1 => self::BUTTON_12, // Blower #1 maps to BUTTON_12
    ];

    // TODO map needed? there is only one mister
    const MISTER_BUTTON_MAP = [
        1 => self::BUTTON_14, // Blower #1 maps to BUTTON_12
    ];

    const LIGHT_BUTTON_MAP = [
        1 => self::BUTTON_17, // Light #1 maps to BUTTON_17
        2 => self::BUTTON_18, // Light #2 maps to BUTTON_18
    ];

    const AUX_BUTTON_MAP = [
        1 => self::BUTTON_22, // AUX #1 maps to BUTTON_22
        2 => self::BUTTON_23, // AUX #2 maps to BUTTON_23
    ];

    const TEMP_BUTTON_MAP = [
        'TempRange' => self::BUTTON_80,
        'HeatMode'  => self::BUTTON_81,
    ];

    const TARGET_TEMPERATURE_MAP = [
        'C' => [
            PanelUpdate::TEMPERATURE_RANGE_LOW  => [ 10, 37 ],
            PanelUpdate::TEMPERATURE_RANGE_HIGH => [ 26.5, 40 ],
        ],
        'F' => [
            PanelUpdate::TEMPERATURE_RANGE_LOW  => [ 50, 99 ],
            PanelUpdate::TEMPERATURE_RANGE_HIGH => [ 80, 104 ],
        ],
    ];

    /**
     * @var BalboaApi
     */
    private $api;

    /**
     * @var int
     */
    private $timeout = 15;

    /**
     * @var bool
     */
    private $cache = false;

    /**
     * @var DeviceConfiguration
     */
    private $deviceConfiguration;

    /**
     * @var PanelUpdate
     */
    private $panelUpdate;

    /**
     * @param BalboaApi $api
     */
    public function __construct(BalboaApi $api)
    {
        $this->api = $api;
    }

    /**
     * @return BalboaApi
     */
    public function getApi(): BalboaApi
    {
        return $this->api;
    }

    /**
     * @return int
     */
    public function getTimeout(): int
    {
        return $this->timeout;
    }

    /**
     * @param int $timeout
     */
    public function setTimeout(int $timeout): void
    {
        $this->timeout = $timeout;
    }

    /**
     * @return bool
     */
    public function isCache(): bool
    {
        return $this->cache;
    }

    /**
     * @param bool $cache
     */
    public function setCache(bool $cache): void
    {
        $this->cache = $cache;
    }

    /**
     * @return PanelUpdate
     */
    public function getPanelUpdate(): PanelUpdate
    {
        if ($this->panelUpdate instanceof PanelUpdate) {
            return $this->panelUpdate;
        }

        $postFields        = $this->createSciRequestGetFileCommandXml('PanelUpdate.txt');
        $responseData      = $this->requestSciRequestGetFileCommand($postFields);
        $decodedData       = $this->decodeBase64($responseData);
        $this->panelUpdate = new PanelUpdate($decodedData);

        return $this->panelUpdate;
    }

    /**
     * @return DeviceConfiguration
     */
    public function getDeviceConfiguration(): DeviceConfiguration
    {
        if ($this->deviceConfiguration instanceof DeviceConfiguration) {
            return $this->deviceConfiguration;
        }

        $postFields                = $this->createSciRequestGetFileCommandXml('DeviceConfiguration.txt');
        $responseData              = $this->requestSciRequestGetFileCommand($postFields);
        $decodedData               = $this->decodeBase64($responseData);
        $this->deviceConfiguration = new DeviceConfiguration($decodedData);

        return $this->deviceConfiguration;
    }

    /**
     * @param float $temperature
     *
     * @return bool
     */
    public function setTargetTemperature(float $temperature): bool
    {
        $panelUpdate = $this->getPanelUpdate();
        if ($panelUpdate->isTemperatureScaleCelsius()) {
            $temperature = $temperature * 2; // if we're using celsuis, we have to double the value to set the correct temperature
        }

        $temperature = strval($temperature);
        $temperature = str_replace(',', '.', $temperature);

        return $this->deviceRequest(self::TARGET_SET_TEMP, $temperature);
    }

    /**
     * @param string $range low or high are allowed
     *
     * @return bool
     */
    public function setTemperatureRange(string $range): bool
    {
        $range = strtolower($range);

        if (!in_array($range, [ PanelUpdate::TEMPERATURE_RANGE_LOW, PanelUpdate::TEMPERATURE_RANGE_HIGH ])) {
            throw new RuntimeException(sprintf('Temperature range %s is not allowed.', $range));
        }

        $panelUpdate             = $this->getPanelUpdate();
        $currentTemperatureRange = $panelUpdate->getTemperatureRange();

        if ($range === $currentTemperatureRange) {
            return true;
        }

        return $this->deviceRequest(self::TARGET_BUTTON, self::TEMP_BUTTON_MAP['TempRange']);
    }

    /**
     * @param string $mode rest or ready are allowed
     *
     * @return bool
     */
    public function setHeatMode(string $mode): bool
    {
        $mode = strtolower($mode);

        if (!in_array($mode, [ strtolower(PanelUpdate::HEAT_MODE_REST), strtolower(PanelUpdate::HEAT_MODE_READY) ])) {
            throw new RuntimeException(sprintf('Heat mode %s is not allowed.', $mode));
        }

        $panelUpdate     = $this->getPanelUpdate();
        $currentHeatMode = $panelUpdate->getHeatMode();

        if ($mode === $currentHeatMode) {
            return true;
        }

        return $this->deviceRequest(self::TARGET_BUTTON, self::TEMP_BUTTON_MAP['HeatMode']);
    }

    /**
     * @param int $pump
     *
     * @return bool
     */
    public function turnOnPump(int $pump): bool
    {
        return $this->deviceRequest(self::TARGET_BUTTON, self::PUMP_BUTTON_MAP[$pump], self::ACTION_ON);
    }

    /**
     * @param int $pump
     *
     * @return bool
     */
    public function turnOffPump(int $pump): bool
    {
        return $this->deviceRequest(self::TARGET_BUTTON, self::PUMP_BUTTON_MAP[$pump], self::ACTION_OFF);
    }

    /**
     * @return bool
     */
    public function turnOnBlower(): bool
    {
        return $this->deviceRequest(self::TARGET_BUTTON, self::BLOWER_BUTTON_MAP[1], self::ACTION_ON);
    }

    /**
     * @return bool
     */
    public function turnOffBlower(): bool
    {
        return $this->deviceRequest(self::TARGET_BUTTON, self::BLOWER_BUTTON_MAP[1], self::ACTION_OFF);
    }

    /**
     * @param int $light
     *
     * @return bool
     */
    public function turnOnLight(int $light): bool
    {
        return $this->deviceRequest(self::TARGET_BUTTON, self::LIGHT_BUTTON_MAP[$light], self::ACTION_ON);
    }

    /**
     * @param int $light
     *
     * @return bool
     */
    public function turnOffLight(int $light): bool
    {
        return $this->deviceRequest(self::TARGET_BUTTON, self::LIGHT_BUTTON_MAP[$light], self::ACTION_OFF);
    }

    /**
     * @param int $light
     *
     * @return bool
     */
    public function turnOnAux(int $aux): bool
    {
        return $this->deviceRequest(self::TARGET_BUTTON, self::AUX_BUTTON_MAP[$aux], self::ACTION_ON);
    }

    /**
     * @param int $light
     *
     * @return bool
     */
    public function turnOffAux(int $aux): bool
    {
        return $this->deviceRequest(self::TARGET_BUTTON, self::AUX_BUTTON_MAP[$aux], self::ACTION_OFF);
    }

    /**
     * Examples:
     * ---------
     *
     * 1. Set temperature to 35 °C
     * BalboaClient::deviceRequest('35', BalboaClient::TARGET_SET_TEMP)
     *
     * 2. Activate Pump 2
     * BalboaClient::deviceRequest(BalboaClient::TARGET_BUTTON, BalboaClient::PUMP_BUTTON_MAP[2],
     * BalboaClient::ACTION_ON)
     *
     * 3. Deactivate Pump 5
     * BalboaClient::deviceRequest(BalboaClient::TARGET_BUTTON, BalboaClient::PUMP_BUTTON_MAP[5],
     * BalboaClient::ACTION_OFF)
     *
     * @param string      $targetName
     * @param string      $targetValue
     * @param string|null $valueAction optional, only for BalboaClient::TARGET_BUTTON
     *
     * @return bool
     */
    public function deviceRequest(string $targetName, string $targetValue, string $valueAction = null): bool
    {
        if ($targetName === self::TARGET_BUTTON && $valueAction !== null) {
            $targetValue = sprintf('%s:%s', $targetValue, $valueAction);
        }

        $postFields = $this->createSciRequestDeviceRequestXml($targetName, $targetValue);

        return $this->requestSciRequestDeviceRequest($postFields);
    }

    /**
     * @param string $file
     *
     * @return string
     */
    private function createSciRequestGetFileCommandXml(string $file): string
    {
        return sprintf(
            '<sci_request version="1.0"><file_system cache="%s"><targets><device id="%s"/></targets><commands><get_file path="%s" syncTimeout="%d"/></commands></file_system></sci_request>',
            $this->isCache() ? 'true' : 'false',
            $this->getApi()->getDeviceId(),
            $file,
            $this->getTimeout()
        );
    }

    /**
     * @param string $targetName
     * @param string $targetValue
     *
     * @return string
     */
    private function createSciRequestDeviceRequestXml(string $targetName, string $targetValue): string
    {
        return sprintf(
            '<sci_request version="1.0"><data_service><targets><device id="%s"/></targets><requests><device_request target_name="%s">%s</device_request></requests></data_service></sci_request>',
            $this->getApi()->getDeviceId(),
            $targetName,
            $targetValue
        );
    }

    /**
     * @param string $postFields
     *
     * @return string
     */
    private function requestSciRequestGetFileCommand(string $postFields): string
    {
        $response = $this->requestDevicesSci($postFields);

        return $this->extractDataFromXml($response->getBody());
    }

    /**
     * @param string $postFields
     *
     * @return bool
     */
    private function requestSciRequestDeviceRequest(string $postFields): bool
    {
        $response = $this->requestDevicesSci($postFields);

        return (strpos($response->getBody(), 'Command received') !== false);
    }

    /**
     * @param string $postFields
     *
     * @return BalboaApiResponse
     */
    private function requestDevicesSci(string $postFields): BalboaApiResponse
    {
        $response = $this->getApi()->devicesSci($postFields);

        if ($response->hasError()) {
            throw new RuntimeException($response->getError());
        }

        return $response;
    }

    /**
     * @param string $body
     *
     * @return string
     */
    private function extractDataFromXml(string $body): string
    {
        preg_match('~<data>(.*?)</data>~', $body, $matches);

        return $matches[1] ?? $body;
    }

    /**
     * @param string $input base64
     *
     * @return array
     */
    public function decodeBase64(string $input): array
    {
        $input = mb_convert_encoding($input, 'UTF-8', 'ISO-8859-1');
        $input = base64_decode($input);

        if (!$input) {
            throw new RuntimeException('Could not decode expected base64 input.');
        }

        $output    = [];
        $output[0] = 126;
        $max       = strlen($input);

        for ($i = 0; $i < $max; $i++) {
            $output[] = ord($input[$i]);
        }

        return $output;
    }

}
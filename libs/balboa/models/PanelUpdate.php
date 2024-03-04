<?php

class PanelUpdate implements \JsonSerializable
{

    const FILTER_MODE_1   = 'filter 1';
    const FILTER_MODE_2   = 'filter 2';
    const FILTER_MODE_ALL = 'filter 1 & 2';
    const FILTER_MODE_OFF = 'off';

    const HEAT_MODE_READY         = 'ready';
    const HEAT_MODE_REST          = 'rest';
    const HEAT_MODE_READY_IN_REST = 'ready in rest';
    const HEAT_MODE_NONE          = 'none';

    const TEMPERATURE_RANGE_LOW  = 'low';
    const TEMPERATURE_RANGE_HIGH = 'high';

    const PUMP_STATE_LOW       = 'low';
    const PUMP_STATE_LOW_HEAT  = 'low heat';
    const PUMP_STATE_HIGH      = 'high';
    const PUMP_STATE_HIGH_HEAT = 'high heat';
    const PUMP_STATE_OFF       = 'off';

    const BLOWER_STATE_LOW    = 'low';
    const BLOWER_STATE_MEDIUM = 'medium';
    const BLOWER_STATE_HIGH   = 'high';
    const BLOWER_STATE_OFF    = 'off';

    const WIFI_STATE_UNKNOWN           = 'unknown';
    const WIFI_STATE_OK                = 'ok';
    const WIFI_STATE_NOT_COMMUNICATING = 'not communicating';
    const WIFI_STATE_STARTUP           = 'startup';
    const WIFI_STATE_PRIME             = 'prime';
    const WIFI_STATE_HOLD              = 'hold';
    const WIFI_STATE_PANEL             = 'panel';

    const STATE_ON  = 'on';
    const STATE_OFF = 'off';

    /**
     * @var array
     */
    private $byteData;

    /**
     * @param array $byteData
     */
    public function __construct(array $byteData)
    {
        $this->byteData = $byteData;
    }

    /**
     * @param int|null $index
     *
     * @return array|mixed
     */
    public function getByteData(int $index = null)
    {
        if ($index !== null && $index >= 0) {
            if (!isset($this->byteData[$index])) {
                throw new RuntimeException('Index not found');
            }

            return $this->byteData[$index];
        }

        return $this->byteData;
    }

    /**
     * Whether temperature scale is celsius
     *
     * @return bool
     */
    public function isTemperatureScaleCelsius(): bool
    {
        return (($this->getByteData(14) & 1) !== 0);
    }

    /**
     * Whether heating mode is set to high
     *
     * @return bool
     */
    public function isHeating(): bool
    {
        return (($this->getByteData(15) & 48) !== 0);
    }

    /**
     * Get current temperature
     *
     * Note: Balboa uses a wrong fahrenheit scale.
     *
     * @return float
     */
    public function getCurrentTemperature(): float
    {
        $value = $this->getByteData(7);
        if ($value === 255) {
            return (float) $value;
        }

        return $this->isTemperatureScaleCelsius() ? (float) ($value / 2) : (float) $value;
    }

    /**
     * Get target temperature
     *
     * @return float
     */
    public function getTargetTemperature(): float
    {
        $value = $this->getByteData(25);
        if ($value === 255) {
            return (float) $value;
        }

        return $this->isTemperatureScaleCelsius() ? (float) ($value / 2) : (float) $value;
    }

    /**
     * Get current filter cycle mode
     *
     * @return string
     */
    public function getFilterMode(): string
    {
        $filterMode = $this->getByteData(14) & 12;

        switch ($filterMode) {
            case 4:
                return self::FILTER_MODE_1;
            case 12:
                return self::FILTER_MODE_ALL;
            case 8:
                return self::FILTER_MODE_2;
            case 0:
            default:
                return self::STATE_OFF;
        }
    }

    public function getAccessibilityType(): string
    {
        $value = $this->getByteData(14) & 48;
        switch ($value) {
            case 16:
                return "Pump light";
            case 32:
            case 42:
                return "None";
            case 0:
            default:
                return "All";
        }
    }

    /**
     * @return string
     */
    public function getHour(): string
    {
        return $this->getByteData(8);
    }

    /**
     * @return string
     */
    public function getMinute(): string
    {
        return $this->getByteData(9);
    }

    /**
     * @return bool
     */
    public function is24HourFormat(): bool
    {
        return (($this->getByteData(14) & 2) !== 0);
    }

    /**
     * @return bool
     */
    public function isLight1On(): bool
    {
        return (($this->getByteData(19) & 3) !== 0);
    }

    /**
     * @return bool
     */
    public function isLight2On(): bool
    {
        return (($this->getByteData(12) & 12) !== 0);
    }

    /**
     * Get heating mode
     */
    public function getHeatMode(): string
    {
        switch ($this->getByteData(10)) {
            case 0:
                return self::HEAT_MODE_READY;
            case 1:
                return self::HEAT_MODE_REST;
            case 2:
                return self::HEAT_MODE_READY_IN_REST;
            default:
                return self::HEAT_MODE_NONE;
        }
    }

    /**
     * @return string
     */
    public function getTemperatureRange(): string
    {
        return ($this->getByteData(15) & 4) === 4 ? self::TEMPERATURE_RANGE_HIGH : self::TEMPERATURE_RANGE_LOW;
    }

    /**
     * @return string
     */
    public function getPump1Status(): string
    {
        $byte15 = $this->getByteData(15) & 48;
        $byte16 = $this->getByteData(16) & 3;

        if ($byte16 === 1) {
            if ($byte15 === 0) {  //OR device has pomp 0
                return self::PUMP_STATE_LOW;
            }
            return self::PUMP_STATE_LOW_HEAT;
        }

        if ($byte16 !== 2) {
            return self::PUMP_STATE_OFF;
        }

        if ($byte15 === 0) {
            return self::PUMP_STATE_HIGH;
        }

        return self::PUMP_STATE_HIGH_HEAT;
    }

    /**
     * @return string
     */
    public function getPump2Status(): string
    {
        switch ($this->getByteData(16) & 12) {
            case 4:
                return self::PUMP_STATE_LOW;
            case 8:
                return self::PUMP_STATE_HIGH;
            default:
                return self::PUMP_STATE_OFF;
        }
    }

    /**
     * @return string
     */
    public function getPump3Status(): string
    {
        switch ($this->getByteData(16) & 48) {
            case 4:
                return self::PUMP_STATE_LOW;
            case 8:
                return self::PUMP_STATE_HIGH;
            default:
                return self::PUMP_STATE_OFF;
        }
    }

    /**
     * @return string
     */
    public function getPump4Status(): string
    {
        switch ($this->getByteData(16) & 192) {
            case 4:
                return self::PUMP_STATE_LOW;
            case 8:
                return self::PUMP_STATE_HIGH;
            default:
                return self::PUMP_STATE_OFF;
        }
    }

    /**
     * @return string
     */
    public function getPump5Status(): string
    {
        switch ($this->getByteData(17) & 3) {
            case 4:
                return self::PUMP_STATE_LOW;
            case 8:
                return self::PUMP_STATE_HIGH;
            default:
                return self::PUMP_STATE_OFF;
        }
    }

    /**
     * @return string
     */
    public function getPump6Status(): string
    {
        switch ($this->getByteData(17) & 12) {
            case 4:
                return self::PUMP_STATE_LOW;
            case 8:
                return self::PUMP_STATE_HIGH;
            default:
                return self::PUMP_STATE_OFF;
        }
    }

    /**
     * @return bool
     */
    public function isMisterOn(): bool
    {
        return (($this->getByteData(20) & 1) !== 0);
    }

    /**
     * @return bool
     */
    public function isAux1On(): bool
    {
        return (($this->getByteData(20) & 8) !== 0);
    }

    /**
     * @return bool
     */
    public function isAux2On(): bool
    {
        return (($this->getByteData(20) & 16) !== 0);
    }

    /**
     * @return string
     */
    public function getBlowerStatus()
    {
        switch ($this->getByteData(18) & 12) {
            case 4:
                return self::BLOWER_STATE_LOW;
            case 8:
                return self::BLOWER_STATE_MEDIUM;
            case 12:
                return self::BLOWER_STATE_HIGH;
            default:
                return self::BLOWER_STATE_OFF;
        }
    }

    /**
     * @return string
     */
    public function getPumpStatus(): string
    {
        $byte152 = $this->getByteData(15) & 48;
        $byte10  = $this->getByteData(18) & 3;

        if ($this->getByteData(16) < 1 && $this->getByteData(17) < 1 && $byte10 < 1) {
            return self::PUMP_STATE_OFF;
        }

        if ($byte152 === 0) {
            return self::PUMP_STATE_LOW;
        }

        return self::PUMP_STATE_LOW_HEAT;
    }

    /**
     * @return string
     */
    public function getWifiStatus(): string
    {
        switch ($this->getByteData(27) & 240) {
            case 0:
                return self::WIFI_STATE_OK;
            default:
            case 16:
                return self::WIFI_STATE_NOT_COMMUNICATING;
            case 32:
                return self::WIFI_STATE_STARTUP;
            case 48:
                return self::WIFI_STATE_PRIME;
            case 64:
                return self::WIFI_STATE_HOLD;
            case 80:
                return self::WIFI_STATE_PANEL;
        }
    }

    /**
     * @return array|mixed
     */
    public function jsonSerialize()
    {
        return get_object_vars($this);
    }

}
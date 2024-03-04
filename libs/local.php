<?php

declare(strict_types=1);

trait BalboaSpaControlLocalLib
{

    public static $IS_UNAUTHORIZED = IS_EBASE + 10;
    public static $IS_SERVERERROR  = IS_EBASE + 11;
    public static $IS_HTTPERROR    = IS_EBASE + 12;
    public static $IS_INVALIDDATA  = IS_EBASE + 13;
    public static $IS_NOLOGIN      = IS_EBASE + 14;
    public static $IS_NODATA       = IS_EBASE + 15;

    private function GetFormStatus()
    {
        $formStatus = $this->GetCommonFormStatus();

        $formStatus[] = [ 'code' => self::$IS_UNAUTHORIZED, 'icon' => 'error', 'caption' => 'Instance is inactive (Not unauthorized)' ];
        $formStatus[] = [ 'code' => self::$IS_SERVERERROR, 'icon' => 'error', 'caption' => 'Instance is inactive (Server error)' ];
        $formStatus[] = [ 'code' => self::$IS_HTTPERROR, 'icon' => 'error', 'caption' => 'Instance is inactive (HTTP error)' ];
        $formStatus[] = [ 'code' => self::$IS_INVALIDDATA, 'icon' => 'error', 'caption' => 'Instance is inactive (Invalid data)' ];
        $formStatus[] = [ 'code' => self::$IS_NOLOGIN, 'icon' => 'error', 'caption' => 'Instance is inactive (Not logged in)' ];
        $formStatus[] = [ 'code' => self::$IS_NODATA, 'icon' => 'error', 'caption' => 'Instance is inactive (No data)' ];

        return $formStatus;
    }

    public static $STATUS_INVALID   = 0;
    public static $STATUS_VALID     = 1;
    public static $STATUS_RETRYABLE = 2;
    public static $STATUS_LOCKED    = 3;

    private function CheckStatus()
    {
        switch ($this->GetStatus()) {
            case IS_ACTIVE:
                $class = self::$STATUS_VALID;
                break;
            case self::$IS_NODATA:
            case self::$IS_UNAUTHORIZED:
            case self::$IS_SERVERERROR:
            case self::$IS_HTTPERROR:
                $class = self::$STATUS_RETRYABLE;
                break;
            case self::$IS_INVALIDDATA:
                $class = self::$STATUS_LOCKED;
                break;
            default:
                $class = self::$STATUS_INVALID;
                break;
        }

        return $class;
    }

    private function InstallVarProfiles(bool $reInstall = false)
    {
        if ($reInstall) {
            $this->SendDebug(__FUNCTION__, 'reInstall=' . $this->bool2str($reInstall), 0);
        }

        // Pump/Blower/Aux/Light On/Off
        $associations = [
            [ 'Wert' => false, 'Name' => $this->Translate('Off'), 'Farbe' => -1 ],
            [ 'Wert' => true, 'Name' => $this->Translate('On'), 'Farbe' => -1 ],
        ];
        $this->CreateVarProfile('BalboaSpaControl.OnOff', VARIABLETYPE_BOOLEAN, '', 0, 0, 0, 0, '', $associations, $reInstall);

        // State as string On/Off
        $associations = [
            [ 'Wert' => PanelUpdate::STATE_OFF, 'Name' => $this->Translate('Off'), 'Farbe' => -1 ],
            [ 'Wert' => PanelUpdate::STATE_ON, 'Name' => $this->Translate('On'), 'Farbe' => -1 ],
        ];
        $this->CreateVarProfile('BalboaSpaControl.State', VARIABLETYPE_STRING, '', 0, 0, 0, 0, '', $associations, $reInstall);

        // Status Yes/No
        $associations = [
            [ 'Wert' => false, 'Name' => $this->Translate('No'), 'Farbe' => -1 ],
            [ 'Wert' => true, 'Name' => $this->Translate('Yes'), 'Farbe' => -1 ],
        ];
        $this->CreateVarProfile('BalboaSpaControl.YesNo', VARIABLETYPE_BOOLEAN, '', 0, 0, 0, 0, '', $associations, $reInstall);

        $associations = [
            [ 'Wert' => PanelUpdate::HEAT_MODE_READY, 'Name' => $this->Translate('Ready'), 'Farbe' => -1 ],
            [ 'Wert' => PanelUpdate::HEAT_MODE_REST, 'Name' => $this->Translate('Rest'), 'Farbe' => -1 ],
            [ 'Wert' => PanelUpdate::HEAT_MODE_READY_IN_REST, 'Name' => $this->Translate('Ready in rest'), 'Farbe' => -1 ],
            [ 'Wert' => PanelUpdate::HEAT_MODE_NONE, 'Name' => $this->Translate('None'), 'Farbe' => -1 ],
        ];
        $this->CreateVarProfile('BalboaSpaControl.HeatMode', VARIABLETYPE_STRING, '', 0, 0, 0, 0, '', $associations, $reInstall);

        $associations = [
            [ 'Wert' => PanelUpdate::FILTER_MODE_OFF, 'Name' => $this->Translate('Off'), 'Farbe' => -1 ],
            [ 'Wert' => PanelUpdate::FILTER_MODE_1, 'Name' => $this->Translate('Filter 1'), 'Farbe' => -1 ],
            [ 'Wert' => PanelUpdate::FILTER_MODE_2, 'Name' => $this->Translate('Filter 2'), 'Farbe' => -1 ],
            [ 'Wert' => PanelUpdate::FILTER_MODE_ALL, 'Name' => $this->Translate('Filter 1 & 2'), 'Farbe' => -1 ],
        ];
        $this->CreateVarProfile('BalboaSpaControl.FilterMode', VARIABLETYPE_STRING, '', 0, 0, 0, 0, '', $associations, $reInstall);

        $associations = [
            [ 'Wert' => PanelUpdate::TEMPERATURE_RANGE_LOW, 'Name' => $this->Translate('Low Range'), 'Farbe' => -1 ],
            [ 'Wert' => PanelUpdate::TEMPERATURE_RANGE_HIGH, 'Name' => $this->Translate('High Range'), 'Farbe' => -1 ],
        ];
        $this->CreateVarProfile('BalboaSpaControl.TemperatureRange', VARIABLETYPE_STRING, '', 0, 0, 0, 0, '', $associations, $reInstall);

        $associations = [
            [ 'Wert' => PanelUpdate::PUMP_STATE_OFF, 'Name' => $this->Translate('Off'), 'Farbe' => -1 ],
            [ 'Wert' => PanelUpdate::PUMP_STATE_LOW_HEAT, 'Name' => $this->Translate('Low Heat'), 'Farbe' => -1 ],
            [ 'Wert' => PanelUpdate::PUMP_STATE_LOW, 'Name' => $this->Translate('Low'), 'Farbe' => -1 ],
            [ 'Wert' => PanelUpdate::PUMP_STATE_HIGH_HEAT, 'Name' => $this->Translate('High Heat'), 'Farbe' => -1 ],
            [ 'Wert' => PanelUpdate::PUMP_STATE_HIGH, 'Name' => $this->Translate('High'), 'Farbe' => -1 ],
        ];
        $this->CreateVarProfile('BalboaSpaControl.PumpState', VARIABLETYPE_STRING, '', 0, 0, 0, 0, '', $associations, $reInstall);

        $associations = [
            [ 'Wert' => PanelUpdate::BLOWER_STATE_OFF, 'Name' => $this->Translate('Off'), 'Farbe' => -1 ],
            [ 'Wert' => PanelUpdate::BLOWER_STATE_LOW, 'Name' => $this->Translate('Low'), 'Farbe' => -1 ],
            [ 'Wert' => PanelUpdate::BLOWER_STATE_MEDIUM, 'Name' => $this->Translate('Medium'), 'Farbe' => -1 ],
            [ 'Wert' => PanelUpdate::BLOWER_STATE_HIGH, 'Name' => $this->Translate('High'), 'Farbe' => -1 ],
        ];
        $this->CreateVarProfile('BalboaSpaControl.BlowerState', VARIABLETYPE_STRING, '', 0, 0, 0, 0, '', $associations, $reInstall);

        $associations = [
            [ 'Wert' => PanelUpdate::WIFI_STATE_UNKNOWN, 'Name' => $this->Translate('Unknown'), 'Farbe' => -1 ],
            [ 'Wert' => PanelUpdate::WIFI_STATE_OK, 'Name' => $this->Translate('OK'), 'Farbe' => -1 ],
            [ 'Wert' => PanelUpdate::WIFI_STATE_NOT_COMMUNICATING, 'Name' => $this->Translate('Not communicating'), 'Farbe' => -1 ],
            [ 'Wert' => PanelUpdate::WIFI_STATE_STARTUP, 'Name' => $this->Translate('Startup'), 'Farbe' => -1 ],
            [ 'Wert' => PanelUpdate::WIFI_STATE_PRIME, 'Name' => $this->Translate('Priming'), 'Farbe' => -1 ],
            [ 'Wert' => PanelUpdate::WIFI_STATE_HOLD, 'Name' => $this->Translate('Holding'), 'Farbe' => -1 ],
            [ 'Wert' => PanelUpdate::WIFI_STATE_PANEL, 'Name' => $this->Translate('Panel'), 'Farbe' => -1 ],
        ];
        $this->CreateVarProfile('BalboaSpaControl.WiFiState', VARIABLETYPE_STRING, '', 0, 0, 0, 0, '', $associations, $reInstall);

        $this->CreateVarProfile('BalboaSpaControl.Temperature', VARIABLETYPE_FLOAT, '', 0, 0, 0.5, 1, '', [], $reInstall);
        $this->CreateVarProfile('BalboaSpaControl.TemperatureCelsius', VARIABLETYPE_FLOAT, '°C', 0, 0, 0.5, 1, '', [], $reInstall);
        $this->CreateVarProfile('BalboaSpaControl.TemperatureFahrenheit', VARIABLETYPE_FLOAT, 'F', 0, 0, 1, 0, '', [], $reInstall);
        $this->CreateVarProfile('BalboaSpaControl.Hour', VARIABLETYPE_INTEGER, '', 0, 23, 1, 0, '', [], $reInstall);
        $this->CreateVarProfile('BalboaSpaControl.Minute', VARIABLETYPE_INTEGER, '', 0, 59, 1, 0, '', [], $reInstall);
    }

}
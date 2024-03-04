<?php

declare(strict_types=1);

require_once __DIR__ . '/../libs/common.php';
require_once __DIR__ . '/../libs/local.php';

class BalboaSpaControlDevice extends IPSModule
{

    use BalboaSpaControl\StubsCommonLib;
    use BalboaSpaControlLocalLib;

    const MODULE_GUID_IO = '{5F0B449F-BA34-23D5-C27F-191DA3F16F08}';

    public function __construct(string $InstanceID)
    {
        parent::__construct($InstanceID);

        $this->CommonContruct(__DIR__);
    }

    public function __destruct()
    {
        $this->CommonDestruct();
    }

    public function Create()
    {
        //Never delete this line!
        parent::Create();
        //$this->ConnectParent('{BF9EC368-97BF-E08B-95D7-ADC165B13B92}'); // connect to IO
        //$this->ConnectParent('{36609F68-103B-B0CC-FA97-45FA2E1F436D}'); // connect to IO

        $this->RegisterPropertyBoolean('module_disable', false);

        // default device configuration
        $this->RegisterPropertyBoolean('pump1_active', true);
        $this->RegisterPropertyBoolean('pump2_active', false);
        $this->RegisterPropertyBoolean('blower_active', true);
        $this->RegisterPropertyBoolean('aux1_active', true);
        $this->RegisterPropertyBoolean('aux2_active', false);
        $this->RegisterPropertyBoolean('light1_active', true);
        $this->RegisterPropertyBoolean('light2_active', false);

        $this->RegisterPropertyBoolean('log_no_parent', true);

        $this->RegisterPropertyString('GUID', '');     // full device id
        $this->RegisterPropertyString('DeviceID', ''); // last block of device id

        // Last error message, normally something about reed door
        //$this->RegisterPropertyString('LastError', '');

        $this->RegisterPropertyInteger('UpdateInterval', 30);
        $this->RegisterAttributeString('UpdateInfo', json_encode([]));
        $this->RegisterAttributeString('ModuleStats', json_encode([]));

        $this->InstallVarProfiles(false);

        $this->RegisterTimer('UpdateData', 0, 'IPS_RequestAction(' . $this->InstanceID . ', "UpdateData", "");');

        $this->RegisterMessage(0, IPS_KERNELMESSAGE);
    }

    public function Destroy()
    {
        //Never delete this line!
        parent::Destroy();
    }

    private function MaintainStateVariable($ident, $use, $vpos)
    {
        // TODO understand?
        /*
        $definitions = [
            'HeatState' => [
                'desc'    => 'state',
                'vartype' => VARIABLETYPE_STRING,
                'varprof' => 'BalboaSpaControl.HeatState',
            ],
            'State'     => [
                'desc'    => 'state',
                'vartype' => VARIABLETYPE_BOOLEAN,
                'varprof' => 'BalboaSpaControl.State',
            ],
            'Power'     => [
                'desc'    => 'power state',
                'vartype' => VARIABLETYPE_BOOLEAN,
                'varprof' => 'BalboaSpaControl.YesNo',
                //'varprof' => '~Switch',
            ],
        ];
        */

        if (isset($definitions[$ident])) {
            $this->MaintainVariable($ident, $this->Translate($definitions[$ident]['desc']), $definitions[$ident]['vartype'], $definitions[$ident]['varprof'], $vpos, $use);
        }
    }

    private function CheckModuleConfiguration()
    {
        $result = [];

        return $result;
    }

    private function CheckModuleUpdate(array $oldInfo, array $newInfo)
    {
        $result = [];

        return $result;
    }

    public function ApplyChanges()
    {
        //Never delete this line!
        parent::ApplyChanges();

        $this->MaintainReferences();

        if ($this->CheckPrerequisites() != false) {
            $this->MaintainTimer('UpdateData', 0);
            $this->MaintainStatus(self::$IS_INVALIDPREREQUISITES);
            return;
        }

        if ($this->CheckUpdate() != false) {
            $this->MaintainTimer('UpdateData', 0);
            $this->MaintainStatus(self::$IS_UPDATEUNCOMPLETED);
            return;
        }

        if ($this->CheckConfiguration() != false) {
            $this->MaintainTimer('UpdateData', 0);
            $this->MaintainStatus(self::$IS_INVALIDCONFIG);
            return;
        }

        $vPos = 1; // variable position

        $this->MaintainVariable('HeatMode', $this->Translate('Heating Mode'), VARIABLETYPE_STRING, 'BalboaSpaControl.HeatMode', $vPos++, true);
        $this->MaintainVariable('Heating', $this->Translate('Heating'), VARIABLETYPE_BOOLEAN, 'BalboaSpaControl.YesNo', $vPos++, true);
        $this->MaintainVariable('TemperatureCelsius', $this->Translate('Temperature in celcius'), VARIABLETYPE_BOOLEAN, 'BalboaSpaControl.YesNo', $vPos++, true);
        $this->MaintainVariable('CurrentTemperature', $this->Translate('Current temperature'), VARIABLETYPE_FLOAT, 'BalboaSpaControl.Temperature', $vPos++, true);
        $this->MaintainVariable('TargetTemperature', $this->Translate('Target temperature'), VARIABLETYPE_FLOAT, 'BalboaSpaControl.Temperature', $vPos++, true);
        $this->MaintainVariable('TemperatureRange', $this->Translate('Temperature Range'), VARIABLETYPE_STRING, 'BalboaSpaControl.TemperatureRange', $vPos++, true);
        $this->MaintainVariable('FilterMode', $this->Translate('Current Filter Mode'), VARIABLETYPE_STRING, 'BalboaSpaControl.FilterMode', $vPos++, true);
        $this->MaintainVariable('Blower', $this->Translate('Blower'), VARIABLETYPE_STRING, 'BalboaSpaControl.BlowerState', $vPos++, true);
        $this->MaintainVariable('Pump1', $this->Translate('Pump #1'), VARIABLETYPE_STRING, 'BalboaSpaControl.PumpState', $vPos++, true);
        $this->MaintainVariable('Pump2', $this->Translate('Pump #2'), VARIABLETYPE_STRING, 'BalboaSpaControl.PumpState', $vPos++, true);
        $this->MaintainVariable('Light1', $this->Translate('Light #1'), VARIABLETYPE_STRING, 'BalboaSpaControl.State', $vPos++, true);
        $this->MaintainVariable('Light2', $this->Translate('Light #2'), VARIABLETYPE_STRING, 'BalboaSpaControl.State', $vPos++, true);
        $this->MaintainVariable('Aux1', $this->Translate('Aux #1'), VARIABLETYPE_STRING, 'BalboaSpaControl.State', $vPos++, true);
        $this->MaintainVariable('Aux2', $this->Translate('Aux #2'), VARIABLETYPE_STRING, 'BalboaSpaControl.State', $vPos++, true);
        $this->MaintainVariable('24TimeFormat', $this->Translate('24H Time Format'), VARIABLETYPE_BOOLEAN, 'BalboaSpaControl.YesNo', $vPos++, true);
        $this->MaintainVariable('CurrentHour', $this->Translate('Panel Hour'), VARIABLETYPE_INTEGER, 'BalboaSpaControl.Hour', $vPos++, true);
        $this->MaintainVariable('CurrentMinute', $this->Translate('Panel Minute'), VARIABLETYPE_INTEGER, 'BalboaSpaControl.Minute', $vPos++, true);
        $this->MaintainVariable('WiFiStatus', $this->Translate('WiFi Status'), VARIABLETYPE_STRING, 'BalboaSpaControl.WiFiState', $vPos++, true);
        //$this->MaintainVariable('LastErrorMessage', $this->Translate('Last error message'), VARIABLETYPE_STRING, '', $vPos++, true);

        //$this->EnableAction('HeatMode');
        $this->EnableAction('TargetTemperature');
        $this->EnableAction('TemperatureRange');

        // remove variables if not active
        $pump1Active  = $this->ReadPropertyBoolean('pump1_active');
        $pump2Active  = $this->ReadPropertyBoolean('pump2_active');
        $blowerActive = $this->ReadPropertyBoolean('blower_active');
        $aux1Active   = $this->ReadPropertyBoolean('aux1_active');
        $aux2Active   = $this->ReadPropertyBoolean('aux2_active');
        $light1Active = $this->ReadPropertyBoolean('light1_active');
        $light2Active = $this->ReadPropertyBoolean('light2_active');

        // enable/disable actions for variables
        $variables = [
            'Pump1'  => $pump1Active,
            'Pump2'  => $pump2Active,
            'Blower' => $blowerActive,
            'Aux1'   => $aux1Active,
            'Aux2'   => $aux2Active,
            'Light1' => $light1Active,
            'Light2' => $light2Active,
        ];
        foreach ($variables as $ident => $isActive) {
            if (!$isActive) {
                $this->SendDebug(__FUNCTION__, 'Unregister variable: ' . $ident, 0);
                $this->UnregisterVariable($ident);
            }
        }

        $map = [
            [ 'Pump1' => $pump1Active, 'Pump2' => $pump2Active ],
            [ 'Blower' => $blowerActive ],
            [ 'Aux1' => $aux1Active, 'Aux2' => $aux2Active ],
            [ 'Light1' => $light1Active, 'Light2' => $light2Active ],
        ];
        foreach ($map as $variales) {
            $anyActive = false;
            $idents    = [];
            foreach ($variales as $ident => $isActive) {

                if ($isActive) {
                    $this->DisableAction($ident);
                    $anyActive = true;
                    $idents[]  = $ident;
                }
            }

            if ($anyActive) {
                foreach ($idents as $ident) {
                    $this->EnableAction($ident);
                }
            }
        }

        $this->MaintainStatus(IS_ACTIVE);

        if (IPS_GetKernelRunlevel() == KR_READY) {
            $this->SetUpdateInterval();
        }
    }

    private function GetFormElements()
    {
        $formElements = $this->GetCommonFormElements('Balboa Spa Control Device');

        if ($this->GetStatus() == self::$IS_UPDATEUNCOMPLETED) {
            return $formElements;
        }

        $formElements[] = [
            'type'    => 'CheckBox',
            'name'    => 'module_disable',
            'caption' => 'Disable instance'
        ];

        $formElements[] = [
            'type'    => 'ExpansionPanel',
            'caption' => 'Device Configuration',
            'items'   => [
                [
                    'type'    => 'CheckBox',
                    'enabled' => true,
                    'name'    => 'pump1_active',
                    'caption' => 'Pump #1',
                ],
                [
                    'type'    => 'CheckBox',
                    'enabled' => true,
                    'name'    => 'pump2_active',
                    'caption' => 'Pump #2',
                ],
                [
                    'type'    => 'CheckBox',
                    'enabled' => true,
                    'name'    => 'blower_active',
                    'caption' => 'Blower',
                ],
                [
                    'type'    => 'CheckBox',
                    'enabled' => true,
                    'name'    => 'aux1_active',
                    'caption' => 'AUX #1',
                ],
                [
                    'type'    => 'CheckBox',
                    'enabled' => true,
                    'name'    => 'aux2_active',
                    'caption' => 'AUX #2',
                ],
                [
                    'type'    => 'CheckBox',
                    'enabled' => true,
                    'name'    => 'light1_active',
                    'caption' => 'Light #1',
                ],
                [
                    'type'    => 'CheckBox',
                    'enabled' => true,
                    'name'    => 'light2_active',
                    'caption' => 'Light #2',
                ],
            ],
        ];

        $formElements[] = [
            'type'    => 'ExpansionPanel',
            'caption' => 'Update interval',
            'items'   => [
                [
                    'name'    => 'UpdateInterval',
                    'type'    => 'NumberSpinner',
                    'minimum' => 5,
                    'suffix'  => 'seconds',
                    'caption' => 'Update interval',
                ],
            ],
        ];

        return $formElements;
    }

    public function GetFormActions()
    {
        $formActions = [];

        if ($this->GetStatus() == self::$IS_UPDATEUNCOMPLETED) {
            $formActions[] = $this->GetCompleteUpdateFormAction();

            $formActions[] = $this->GetInformationFormAction();
            $formActions[] = $this->GetReferencesFormAction();

            return $formActions;
        }

        $formActions[] = [
            'type'    => 'Button',
            'caption' => 'Update data',
            'onClick' => 'IPS_RequestAction(' . $this->InstanceID . ', "UpdateData", "");',
        ];

        $formActions[] = [
            'type'     => 'ExpansionPanel',
            'caption'  => 'Expert area',
            'expanded' => false,
            'items'    => [
                $this->GetInstallVarProfilesFormItem(),
            ]
        ];

        $formActions[] = $this->GetInformationFormAction();
        $formActions[] = $this->GetReferencesFormAction();

        return $formActions;
    }

    public function Send()
    {
        $this->SendDataToParent(json_encode([ 'DataID' => '{36609F68-103B-B0CC-FA97-45FA2E1F436D}' ]));
    }

    public function ReceiveData($JSONString)
    {
        $data = json_decode($JSONString);
        //$this->LogMessage('Device RECV', utf8_decode($data->Buffer));
    }

    public function MessageSink($TimeStamp, $SenderID, $Message, $Data)
    {
        parent::MessageSink($TimeStamp, $SenderID, $Message, $Data);

        if ($Message == IPS_KERNELMESSAGE && $Data[0] == KR_READY) {
            $this->SetUpdateInterval();
        }
    }

    public function SetUpdateInterval(int $sec = null)
    {
        if (is_null($sec)) {
            $sec = $this->ReadPropertyInteger('UpdateInterval');
        }
        $msec = $sec * 1000;
        $this->MaintainTimer('UpdateData', $msec);
    }

    public function RequestAction($ident, $value)
    {
        if ($this->ReadPropertyBoolean('module_disable') === true) {
            return;
        }

        if ($this->LocalRequestAction($ident, $value)) {
            return;
        }
        if ($this->CommonRequestAction($ident, $value)) {
            return;
        }

        if ($this->CheckStatus() == self::$STATUS_INVALID) {
            $this->SendDebug(__FUNCTION__, $this->GetStatusText() . ' => skip', 0);
            return;
        }

        $this->SendDebug(__FUNCTION__, 'ident=' . $ident . ', value=' . $value, 0);

        $result = false;
        switch ($ident) {
            case 'HeatMode':
                $this->SendDebug(__FUNCTION__, 'TODO: implement HeatMode function!', 0);
                break;
            case 'TargetTemperature':
                $this->SendDebug(__FUNCTION__, 'TODO: implement TargetTemperature function!', 0);
                break;
            case 'TemperatureRange':
                $this->SendDebug(__FUNCTION__, 'TODO: implement TemperatureRange function!', 0);
                break;
            case 'TurnOnPump':
                $result = $this->SetPump($value, PanelUpdate::PUMP_STATE_HIGH);
                break;
            case 'TurnOffPump':
                $result = $this->SetPump($value, PanelUpdate::PUMP_STATE_OFF);
                break;
            case 'Pump1':
                $result = $this->SetPump(1, $value);
                break;
            case 'Pump2':
                $result = $this->SetPump(2, $value);
                break;
            case 'TogglePump':
                $result = $this->SetPump($value);
                break;
            case 'TurnOnBlower':
                $result = $this->SetBlower(PanelUpdate::BLOWER_STATE_HIGH);
                break;
            case 'TurnOffBlower':
                $result = $this->SetBlower(PanelUpdate::BLOWER_STATE_OFF);
                break;
            case 'Blower':
                $result = $this->SetBlower($value);
                break;
            case 'ToggleBlower':
                $result = $this->SetBlower();
                break;
            case 'TurnOnAux':
                $result = $this->SetAux($value, PanelUpdate::STATE_ON);
                break;
            case 'TurnOffAux':
                $result = $this->SetAux($value, PanelUpdate::STATE_OFF);
                break;
            case 'ToggleAux':
                $result = $this->SetAux($value);
                break;
            case 'Aux1':
                $result = $this->SetAux(1, $value);
                break;
            case 'Aux2':
                $result = $this->SetAux(2, $value);
                break;
            case 'TurnOnLight':
                $result = $this->SetLight($value, PanelUpdate::STATE_ON);
                break;
            case 'TurnOffLight':
                $result = $this->SetLight($value, PanelUpdate::STATE_OFF);
                break;
            case 'ToggleLight':
                $result = $this->SetLight($value);
                break;
            case 'Light1':
                $result = $this->SetLight(1, $value);
                break;
            default:
                $this->SendDebug(__FUNCTION__, 'invalid ident "' . $ident . '"', 0);
        }

        if ($result) {
            $this->SendDebug(__FUNCTION__, 'set ident "' . $ident . '" to value "' . $value . '"', 0);
            $this->SetValue($ident, $value);
        }
    }

    private function LocalRequestAction($ident, $value)
    {
        $result = true;
        switch ($ident) {
            case 'UpdateData':
                $this->UpdateData();
                break;
            default:
                $result = false;
                break;
        }
        return $result;
    }

    /**
     * @param string $function
     * @param array  $parameter
     *
     * @return string
     */
    private function RequestParent(string $function, array $parameter = []): string
    {
        $guid     = $this->ReadPropertyString('GUID');
        $SendData = [
            'DataID'    => self::MODULE_GUID_IO, // to IO module -> implemented
            'CallerID'  => $this->InstanceID,
            'Function'  => $function,
            'ObjectID'  => $guid,
            'AsJson'    => true,
            'Parameter' => $parameter,
        ];

        $result = $this->SendDataToParent(json_encode($SendData));
        $this->SendDebug(__FUNCTION__, ' => data=' . print_r($result, true), 0);

        return $result;
    }

    private function UpdateData()
    {
        if ($this->CheckStatus() == self::$STATUS_INVALID) {
            $this->SendDebug(__FUNCTION__, $this->GetStatusText() . ' => skip', 0);
            return;
        }

        if ($this->HasActiveParent() == false) {
            $this->SendDebug(__FUNCTION__, 'has no active parent/gateway', 0);
            $log_no_parent = $this->ReadPropertyBoolean('log_no_parent');
            if ($log_no_parent) {
                $this->LogMessage($this->Translate('Instance has no active gateway'), KL_WARNING);
            }
            return;
        }

        $this->SetUpdateInterval();

        $result = $this->RequestParent('GetPanelUpdate');
        if ($result === null || $result === 'null') {
            $this->SendDebug(__FUNCTION__, 'No panel data found => skip', 0);
            return;
        }
        $data = json_decode($result, true);

        if (!isset($data['byteData'])) {
            $this->SendDebug(__FUNCTION__, 'No byte data found in result data.', 0);
            return;
        }

        $panelUpdate = new PanelUpdate($data['byteData']);
        $this->updatePanelData($panelUpdate);

        return $panelUpdate;
    }

    /**
     * @param PanelUpdate $panelUpdate
     *
     * @return void
     */
    private function updatePanelData(PanelUpdate $panelUpdate)
    {
        $this->SendDebug(__FUNCTION__, sprintf('Uhrzeit: %d:%d', $panelUpdate->getHour(), $panelUpdate->getMinute()), 0);

        $this->SetValue('HeatMode', $panelUpdate->getHeatMode());
        $this->SetValue('Heating', $panelUpdate->isHeating());
        $this->SetValue('TemperatureCelsius', $panelUpdate->isTemperatureScaleCelsius());
        $this->SetValue('CurrentTemperature', $panelUpdate->getCurrentTemperature());
        $this->SetValue('TargetTemperature', $panelUpdate->getTargetTemperature());
        $this->SetValue('TemperatureRange', $panelUpdate->getTemperatureRange());
        $this->SetValue('FilterMode', $panelUpdate->getFilterMode());
        $this->SetValue('Blower', $panelUpdate->getBlowerStatus());
        $this->SetValue('Pump1', $panelUpdate->getPump1Status());
        $this->SetValue('Pump2', $panelUpdate->getPump2Status());
        $this->SetValue('Aux1', $panelUpdate->isAux1On() ? PanelUpdate::STATE_ON : PanelUpdate::STATE_OFF);
        $this->SetValue('Aux2', $panelUpdate->isAux2On() ? PanelUpdate::STATE_ON : PanelUpdate::STATE_OFF);
        $this->SetValue('Light1', $panelUpdate->isLight1On() ? PanelUpdate::STATE_ON : PanelUpdate::STATE_OFF);
        $this->SetValue('Light2', $panelUpdate->isLight2On() ? PanelUpdate::STATE_ON : PanelUpdate::STATE_OFF);
        $this->SetValue('24TimeFormat', $panelUpdate->is24HourFormat());
        $this->SetValue('CurrentHour', $panelUpdate->getHour());
        $this->SetValue('CurrentMinute', $panelUpdate->getMinute());
        $this->SetValue('WiFiStatus', $panelUpdate->getWifiStatus());
    }

    /**
     * @param string $mode
     *
     * @return bool
     */
    public function SetHeatMode(string $mode): bool
    {
        $mode = strtolower($mode);
        if (!in_array($mode, [ strtolower(PanelUpdate::HEAT_MODE_READY), strtolower(PanelUpdate::HEAT_MODE_REST) ])) {
            return false;
        }

        $currentState = $this->GetValue('HeatMode');
        if ($mode === strtolower($currentState)) {
            $this->SendDebug(__FUNCTION__, sprintf('Heat mode is already set to %s', $currentState), 0);
            return true;
        }

        $result = (bool) $this->RequestParent('SetHeatMode', [ 'mode' => $mode ]);

        if ($result) {
            $this->SetValue('HeatMode', $mode);
        }

        return $result;
    }

    /**
     * @param string $range
     *
     * @return bool
     */
    public function SetTemperatureRange(string $range): bool
    {
        $range = strtolower($range);
        if (!in_array($range, [ strtolower(PanelUpdate::TEMPERATURE_RANGE_LOW), strtolower(PanelUpdate::TEMPERATURE_RANGE_HIGH) ])) {
            return false;
        }

        $currentState = $this->GetValue('TemperatureRange');
        if ($range === strtolower($currentState)) {
            $this->SendDebug(__FUNCTION__, sprintf('Temperature range is already set to %s', $currentState), 0);
            return true;
        }

        $result = (bool) $this->RequestParent('SetTemperatureRange', [ 'range' => $range ]);

        if ($result) {
            $this->SetValue('TemperatureRange', $range);
        }

        return $result;
    }

    /**
     * @param float $temperature
     *
     * @return bool
     */
    public function SetTargetTemperature(float $temperature): bool
    {
        $currentState = $this->GetValue('TargetTemperature');
        if ($temperature === (float) $currentState) {
            $this->SendDebug(__FUNCTION__, sprintf('Temperature is already set to %.1f', $currentState), 0);
            return true;
        }

        $isCelsius        = $this->GetValue('TemperatureCelsius');
        $temperatureRange = $this->GetValue('TemperatureRange');
        $temperatureScale = $isCelsius ? 'C' : 'F';
        $temperatureRange = BalboaClient::TARGET_TEMPERATURE_MAP[$temperatureScale][$temperatureRange];
        $minTemperature   = $temperatureRange[0];
        $maxTemperature   = $temperatureRange[1];

        if ($temperature < $minTemperature || $temperature > $maxTemperature) {
            $this->SendDebug(__FUNCTION__, sprintf('Temperature value "%.1f" is not allowed. Only values between "%.1f" and %.1f are allowed', $temperature, $minTemperature, $maxTemperature), 0);
            return false;
        }

        $result = (bool) $this->RequestParent('SetTargetTemperature', [ 'temperature' => $temperature ]);

        if ($result) {
            $this->SetValue('TargetTemperature', $temperature);
        }

        return $result;
    }

    /**
     * @param int         $number
     * @param string|null $toState
     *
     * @return string
     */
    public function SetPump(int $number, string $toState = null)
    {
        if (!array_key_exists($number, BalboaClient::PUMP_BUTTON_MAP)) {
            $this->SendDebug(__FUNCTION__, sprintf('Pump %d does not exist', $number), 0);
            return false;
        }
        $this->SendDebug(__FUNCTION__, sprintf('set pump %d to %s', $number, $toState === null ? 'toggle' : $toState), 0);

        $parameters = [ 'number' => $number ];
        $turnOn     = true;
        if ($toState === PanelUpdate::PUMP_STATE_OFF) {
            $turnOn = false;
        }
        $parameters['turnOn'] = $turnOn;

        $currentState = $this->GetValue(sprintf('Pump%d', $number)) === PanelUpdate::PUMP_STATE_OFF ? 'off' : 'on';
        if ($currentState === $toState) {
            $this->SendDebug(__FUNCTION__, sprintf('Pump %d is already %s', $number, $currentState), 0);
            return true;
        }

        return (bool) $this->RequestParent('SetPump', $parameters);
    }

    /**
     * @param int $number
     *
     * @return bool
     */
    public function TogglePump(int $number)
    {
        return $this->SetPump($number);
    }

    /**
     * @param string|null $toState
     *
     * @return string
     */
    public function SetBlower(string $toState = null)
    {
        $this->SendDebug(__FUNCTION__, sprintf('set blower to %s', $toState === null ? 'toggle' : $toState), 0);

        $parameters = [ 'number' => 1 ];
        $turnOn     = true;
        if ($toState === PanelUpdate::BLOWER_STATE_OFF) {
            $turnOn = false;
        }
        $parameters['turnOn'] = $turnOn;

        $currentState = $this->GetValue('Blower') === PanelUpdate::BLOWER_STATE_OFF ? 'off' : 'on';
        if ($currentState === $toState) {
            $this->SendDebug(__FUNCTION__, sprintf('Blower is already %s', $currentState), 0);
            return true;
        }

        return (bool) $this->RequestParent('SetBlower', $parameters);
    }

    /**
     * @return bool
     */
    public function ToggleBlower()
    {
        return $this->SetBlower();
    }

    /**
     * @param int         $number
     * @param string|null $toState
     *
     * @return string
     */
    public function SetAux(int $number, string $toState = null)
    {
        if (!array_key_exists($number, BalboaClient::AUX_BUTTON_MAP)) {
            $this->SendDebug(__FUNCTION__, sprintf('Aux %d does not exist', $number), 0);
            return false;
        }
        $this->SendDebug(__FUNCTION__, sprintf('set aux %d to %s', $number, $toState === null ? 'toggle' : $toState), 0);

        $parameters = [ 'number' => $number ];
        $turnOn     = true;
        if ($toState === PanelUpdate::STATE_OFF) {
            $turnOn = false;
        }
        $parameters['turnOn'] = $turnOn;

        $currentState = $this->GetValue(sprintf('Aux%d', $number)) === PanelUpdate::STATE_ON ? 'on' : 'off';
        if ($currentState === $toState) {
            $this->SendDebug(__FUNCTION__, sprintf('Aux %d is already %s', $number, $currentState), 0);
            return true;
        }

        return (bool) $this->RequestParent('SetAux', $parameters);
    }

    /**
     * @param int $number
     *
     * @return bool
     */
    public function ToggleAux(int $number)
    {
        return $this->SetAux($number);
    }

    /**
     * @param int         $number
     * @param string|null $toState
     *
     * @return bool
     */
    public function SetLight(int $number, string $toState = null): bool
    {
        if (!array_key_exists($number, BalboaClient::LIGHT_BUTTON_MAP)) {
            $this->SendDebug(__FUNCTION__, sprintf('Light %d does not exist', $number), 0);
            return false;
        }
        $this->SendDebug(__FUNCTION__, sprintf('Set light %d to %s', $number, $toState === null ? 'toggle' : $toState), 0);

        $parameters = [ 'number' => $number ];
        $turnOn     = true;
        if ($toState === PanelUpdate::STATE_OFF) {
            $turnOn = false;
        }
        $parameters['turnOn'] = $turnOn;

        $currentState = $this->GetValue(sprintf('Light%d', $number)) === PanelUpdate::STATE_ON ? 'on' : 'off';
        if ($currentState === $toState) {
            $this->SendDebug(__FUNCTION__, sprintf('Light %d is already %s', $number, $currentState), 0);
            return true;
        }

        return (bool) $this->RequestParent('SetLight', $parameters);
    }

    /**
     * @param int $number
     *
     * @return bool
     */
    public function ToggleLight(int $number)
    {
        return $this->SetLight($number);
    }

}
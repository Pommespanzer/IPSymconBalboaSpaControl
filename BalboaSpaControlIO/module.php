<?php

declare(strict_types=1);

require_once __DIR__ . '/../libs/common.php';
require_once __DIR__ . '/../libs/local.php';

class BalboaSpaControlIO extends IPSModule
{

    use BalboaSpaControl\StubsCommonLib;
    use BalboaSpaControlLocalLib;

    private static $tokenExpire   = 60 * 60 * 24;
    private static $semaphoreTime = 5 * 1000;

    private $SemaphoreID;

    const BUTTON_TYPE_PUMP   = 'pump';
    const BUTTON_TYPE_BLOWER = 'blower';
    const BUTTON_TYPE_AUX    = 'aux';
    const BUTTON_TYPE_LIGHT  = 'light';

    const ALLOWED_BUTTON_TYPES = [
        self::BUTTON_TYPE_PUMP,
        self::BUTTON_TYPE_BLOWER,
        self::BUTTON_TYPE_AUX,
        self::BUTTON_TYPE_LIGHT,
    ];

    /**
     * @param string $InstanceID
     */
    public function __construct(string $InstanceID)
    {
        parent::__construct($InstanceID);

        $this->CommonContruct(__DIR__);
        $this->SemaphoreID = __CLASS__ . '_' . $InstanceID;
    }

    public function __destruct()
    {
        $this->CommonDestruct();
    }

    public function Create()
    {
        //Never delete this line!
        parent::Create();

        // form fields
        $this->RegisterPropertyBoolean('module_disable', false);
        $this->RegisterPropertyString('username', '');
        $this->RegisterPropertyString('password', '');

        $this->RegisterAttributeString('UpdateInfo', json_encode([]));
        $this->RegisterAttributeString('ApiCallStats', json_encode([]));
        $this->RegisterAttributeString('ModuleStats', json_encode([]));

        $this->InstallVarProfiles(false);

        $this->SetBuffer('LastApiCall', 0);

        $this->RegisterMessage(0, IPS_KERNELMESSAGE);
    }

    private function CheckModuleConfiguration()
    {
        $result = [];

        $username = $this->ReadPropertyString('username');
        $password = $this->ReadPropertyString('password');

        if (empty($username)) {
            $this->SendDebug(__FUNCTION__, '"username" is needed', 0);
            $r[] = $this->Translate('Username must be specified');
        }
        if (empty($password)) {
            $this->SendDebug(__FUNCTION__, '"password" is needed', 0);
            $r[] = $this->Translate('Password must be specified');
        }

        return $result;
    }

    private function CheckApiCallPrerequisites(): bool
    {
        // if instance is inactive, dont offer this function
        if (in_array($this->GetStatus(), [ IS_INACTIVE ], true)) {
            $this->SendDebug(__FUNCTION__, $this->GetStatusText() . ' => skip', 0);
            $this->PopupMessage($this->GetStatusText());

            return false;
        }

        // if username/password is wrong, inform the user
        $status = $this->GetStatus();
        if ($status === self::$IS_INVALIDDATA) {
            $this->SendDebug(__FUNCTION__, ' => statuscode=' . $status, 0);
            $popupMessage = [
                'Username/Password is incorrect!',
            ];
            $this->PopupMessage(implode(PHP_EOL, $popupMessage));

            $this->MaintainStatus(self::$IS_INVALIDDATA);
            return false;
        }

        return true;
    }

    public function Destroy()
    {
        //Never delete this line!
        parent::Destroy();
    }

    public function ApplyChanges()
    {
        //Never delete this line!
        parent::ApplyChanges();

        $this->MaintainReferences();

        if ($this->CheckPrerequisites() != false) {
            $this->MaintainStatus(self::$IS_INVALIDPREREQUISITES);
            return;
        }

        if ($this->CheckUpdate() != false) {
            $this->MaintainStatus(self::$IS_UPDATEUNCOMPLETED);
            return;
        }

        if ($this->CheckConfiguration() != false) {
            $this->MaintainStatus(self::$IS_INVALIDCONFIG);
            return;
        }

        $module_disable = $this->ReadPropertyBoolean('module_disable');
        if ($module_disable) {
            $this->MaintainStatus(IS_INACTIVE);
            return;
        }

        $this->MaintainStatus(IS_ACTIVE);
    }

    public function MessageSink($TimeStamp, $SenderID, $Message, $Data)
    {
        parent::MessageSink($TimeStamp, $SenderID, $Message, $Data);
    }

    /**
     * This function is called by children modules via function SendDataToParent()
     *
     * @param $data
     *
     * @return array|false|string
     */
    public function ForwardData($data)
    {
        if ($this->GetStatus() == IS_INACTIVE) {
            $this->SendDebug(__FUNCTION__, $this->GetStatusText() . ' => skip', 0);
            return false;
        }

        $data = json_decode($data, true);
        $this->SendDebug(__FUNCTION__, 'data=' . print_r($data, true), 0);

        $callerId = $data['CallerID'];
        $this->SendDebug(__FUNCTION__, 'caller=' . $callerId . '(' . IPS_GetName($callerId) . ')', 0);
        $_IPS['CallerID'] = $callerId;

        $params = [];
        if (isset($data['Parameter'])) {
            $params = $data['Parameter'];
        }

        $result = '';
        if (isset($data['Function'])) {
            switch ($data['Function']) {
                case 'GetDevices':
                    $result = $this->GetDevices();
                    break;
                case 'GetPanelUpdate':
                    $result = $this->GetPanelUpdate();
                    break;
                case 'GetDeviceConfiguration':
                    $result = $this->GetDeviceConfigutation();
                    break;
                case 'SetHeatMode':
                    $result = $this->SetHeatMode($params['mode']);
                    break;
                case 'SetTemperatureRange':
                    $result = $this->SetTemperatureRange($params['range']);
                    break;
                case 'SetTargetTemperature':
                    $result = $this->SetTargetTemperature($params['temperature']);
                    break;
                case 'SetPump':
                    $result = $this->SendButton(self::BUTTON_TYPE_PUMP, $params['number'], $params['turnOn'] ?? null);
                    break;
                case 'SetBlower':
                    $result = $this->SendButton(self::BUTTON_TYPE_BLOWER, $params['number'], $params['turnOn'] ?? null);
                    break;
                case 'SetAux':
                    $result = $this->SendButton(self::BUTTON_TYPE_AUX, $params['number'], $params['turnOn'] ?? null);
                    break;
                case 'SetLight':
                    $result = $this->SendButton(self::BUTTON_TYPE_LIGHT, $params['number'], $params['turnOn'] ?? null);
                    break;
            }
        } else {
            $this->SendDebug(__FUNCTION__, 'unknown message-structure', 0);
        }

        $this->SendDebug(__FUNCTION__, 'result=' . print_r($result, true), 0);

        if (isset($data['AsJson']) && $data['AsJson']) {
            return json_encode($result);
        }

        return $result;
    }

    public function Send(string $Text)
    {
        $this->SendDataToChildren(json_encode([ 'DataID' => '', 'Buffer' => $Text ]));
    }


    private function GetFormElements()
    {
        $formElements = $this->GetCommonFormElements('Balboa Spa Control');

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
            'items'   => [
                [
                    'type'    => 'Label',
                    'caption' => implode('\n', [
                        'Log in with your Balboa Spa Control account, created in your Spa Control app.',
                    ]),
                ],
                [
                    'name'    => 'username',
                    'type'    => 'ValidationTextBox',
                    'caption' => 'Username',
                ],
                [
                    'name'    => 'password',
                    'type'    => 'PasswordTextBox',
                    'caption' => 'Password',
                ],
            ],
            'caption' => 'Account data',
        ];

        return $formElements;
    }

    private function getFormActions()
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
            'caption' => 'Test access',
            'onClick' => 'IPS_RequestAction(' . $this->InstanceID . ', "TestAccess", "");',
        ];

        $formActions[] = [
            'type'     => 'ExpansionPanel',
            'caption'  => 'Test Area',
            'expanded' => true,
            'items'    => [
                [
                    'type'    => 'Button',
                    'label'   => 'Get Panel Data',
                    'onClick' => 'IPS_RequestAction(' . $this->InstanceID . ', "TestPanelUpdate", "");',
                ],
                [
                    'type'    => 'Button',
                    'label'   => 'Get Device Configuration',
                    'onClick' => 'IPS_RequestAction(' . $this->InstanceID . ', "TestDeviceConfiguration", "");',
                ],
                [
                    'type'    => 'Button',
                    'label'   => 'Toggle Pump #1',
                    'onClick' => 'IPS_RequestAction(' . $this->InstanceID . ', "TestTogglePump", 1);',
                ],
                [
                    'type'    => 'Button',
                    'label'   => 'Toggle Pump #2',
                    'onClick' => 'IPS_RequestAction(' . $this->InstanceID . ', "TestTogglePump", 2);',
                ],
                [
                    'type'    => 'Button',
                    'label'   => 'Toggle Blower',
                    'onClick' => 'IPS_RequestAction(' . $this->InstanceID . ', "TestToggleBlower", "");',
                ],
                [
                    'type'    => 'Button',
                    'label'   => 'Toggle Light #1',
                    'onClick' => 'IPS_RequestAction(' . $this->InstanceID . ', "TestToggleLight", 1);',
                ],
                [
                    'type'    => 'Button',
                    'label'   => 'Toggle Light #2',
                    'onClick' => 'IPS_RequestAction(' . $this->InstanceID . ', "TestToggleLight", 2);',
                ],
            ],
        ];

        $formActions[] = [
            'type'     => 'ExpansionPanel',
            'caption'  => 'Expert area',
            'expanded' => false,
            'items'    => [
                $this->GetInstallVarProfilesFormItem(),
                [
                    'type'    => 'Button',
                    'label'   => 'Display access token',
                    'onClick' => 'IPS_RequestAction(' . $this->InstanceID . ', "DisplayAccessToken", "");',
                ],
                [
                    'type'    => 'Button',
                    'label'   => 'Clear access token',
                    'onClick' => 'IPS_RequestAction(' . $this->InstanceID . ', "ClearAccessToken", "");',
                ],
                $this->GetApiCallStatsFormItem(),
            ]
        ];

        $formActions[] = $this->GetInformationFormAction();
        $formActions[] = $this->GetReferencesFormAction();

        return $formActions;
    }

    private function LocalRequestAction($ident, $value)
    {
        $r = true;
        switch ($ident) {
            case 'ClearAccessToken':
                $this->ClearAccessToken();
                break;
            case 'DisplayAccessToken':
                $this->DisplayAccessToken();
                break;
            case 'TestAccess':
                $this->TestAccess();
                break;
            case 'TestPanelUpdate':
                $this->TestPanelUpdate();
                break;
            case 'TestDeviceConfiguration':
                $this->TestDeviceConfiguration();
                break;
            case 'TestTogglePump':
                $this->TestTogglePump($value);
                break;
            case 'TestToggleBlower':
                $this->TestToggleBlower();
                break;
            default:
                $r = false;
                break;
        }
        return $r;
    }

    public function RequestAction($ident, $value)
    {
        if ($this->LocalRequestAction($ident, $value)) {
            return;
        }
        if ($this->CommonRequestAction($ident, $value)) {
            return;
        }

        switch ($ident) {
            default:
                $this->SendDebug(__FUNCTION__, 'invalid ident ' . $ident, 0);
                break;
        }
    }

    /**
     * Test if login works. If not, lock login until ResetLoginFailures is called.
     *
     * @return void
     */
    private function TestAccess()
    {
        if (!$this->CheckApiCallPrerequisites()) {
            return false;
        }

        $username = $this->ReadPropertyString('username');
        $password = $this->ReadPropertyString('password');
        if (empty($username) || empty($password)) {
            $this->SendDebug(__FUNCTION__, ' => no username or password given', 0);
            $popupMessage = [
                'No username or password given',
            ];
            $this->PopupMessage(implode(PHP_EOL, $popupMessage));

            $this->MaintainStatus(self::$IS_NODATA);
            return false;
        }

        $client      = $this->getBalboaClient();
        $panelUpdate = $client->getPanelUpdate();

        $this->handleLastRequest($client->getApi()->getLastRequest());
        $this->handleLastResponse($client->getApi()->getLastResponse());

        $popupMessage = [
            'Login successful.',
            sprintf('Device ID: %s', $client->getApi()->getDeviceId()),
        ];

        $this->SendDebug(__FUNCTION__, 'txt=' . print_r($popupMessage, true), 0);
        $this->PopupMessage(implode(PHP_EOL, $popupMessage));
    }

    private function TestPanelUpdate()
    {
        if (!$this->CheckApiCallPrerequisites()) {
            return false;
        }

        $client      = $this->getBalboaClient();
        $panelUpdate = $client->getPanelUpdate();

        $this->handleLastRequest($client->getApi()->getLastRequest());
        $this->handleLastResponse($client->getApi()->getLastResponse());

        $temperatureUnit = $panelUpdate->isTemperatureScaleCelsius() ? '°C' : 'F';

        $popupMessage = [
            $this->Translate('Panel data'),
            sprintf('%s: %s', $this->Translate('Heating mode'), $panelUpdate->getHeatMode()),
            sprintf('%s: %s', $this->Translate('Heating'), $panelUpdate->isHeating() ? $this->Translate('Yes') : $this->Translate('No')),
            sprintf('%s: %s %s', $this->Translate('Current temperature'), $panelUpdate->getCurrentTemperature(), $temperatureUnit),
            sprintf('%s: %s %s', $this->Translate('Target temperature'), $panelUpdate->getTargetTemperature(), $temperatureUnit),
            sprintf('%s: %s', $this->Translate('Temperature range'), $panelUpdate->getTemperatureRange()),
            sprintf('%s: %s', $this->Translate('Filter Mmde'), $panelUpdate->getFilterMode()),
            sprintf('%s: %s', $this->Translate('Blower status'), $panelUpdate->getBlowerStatus()),
            sprintf('%s: %s', $this->Translate('Pump 1 status'), $panelUpdate->getPump1Status()),
            sprintf('%s: %s', $this->Translate('Pump 2 status'), $panelUpdate->getPump2Status()),
            sprintf('%s: %s', $this->Translate('Light 1 status'), $panelUpdate->isLight1On() ? $this->Translate('Yes') : $this->Translate('No')),
            sprintf('%s: %s', $this->Translate('Light 2 status'), $panelUpdate->isLight2On() ? $this->Translate('Yes') : $this->Translate('No')),
            sprintf('%s: %s', $this->Translate('WiFi status'), $panelUpdate->getWifiStatus()),
        ];

        $this->SendDebug(__FUNCTION__, 'txt=' . print_r($popupMessage, true), 0);
        $this->PopupMessage(implode(PHP_EOL, $popupMessage));
    }

    private function TestDeviceConfiguration()
    {
        if (!$this->CheckApiCallPrerequisites()) {
            return false;
        }

        $client              = $this->getBalboaClient();
        $deviceConfiguration = $client->getDeviceConfiguration();

        $this->handleLastRequest($client->getApi()->getLastRequest());
        $this->handleLastResponse($client->getApi()->getLastResponse());

        $popupMessage = [
            $this->Translate('Device configuration'),
            sprintf('%s: %s', $this->Translate('Has Mister'), $deviceConfiguration->hasMister() ? $this->Translate('Yes') : $this->Translate('No')),
            sprintf('%s %d: %s', $this->Translate('Has Pump'), 1, $deviceConfiguration->hasPump1() ? $this->Translate('Yes') : $this->Translate('No')),
            sprintf('%s %d: %s', $this->Translate('Has Pump'), 2, $deviceConfiguration->hasPump2() ? $this->Translate('Yes') : $this->Translate('No')),
            sprintf('%s %d: %s', $this->Translate('Has Pump'), 3, $deviceConfiguration->hasPump3() ? $this->Translate('Yes') : $this->Translate('No')),
            sprintf('%s %d: %s', $this->Translate('Has Pump'), 4, $deviceConfiguration->hasPump4() ? $this->Translate('Yes') : $this->Translate('No')),
            sprintf('%s %d: %s', $this->Translate('Has Pump'), 5, $deviceConfiguration->hasPump5() ? $this->Translate('Yes') : $this->Translate('No')),
            sprintf('%s %d: %s', $this->Translate('Has Pump'), 6, $deviceConfiguration->hasPump6() ? $this->Translate('Yes') : $this->Translate('No')),
            sprintf('%s: %s', $this->Translate('Has Blower'), $deviceConfiguration->hasBlower() ? $this->Translate('Yes') : $this->Translate('No')),
            sprintf('%s %d: %s', $this->Translate('Has Aux'), 1, $deviceConfiguration->hasAux1() ? $this->Translate('Yes') : $this->Translate('No')),
            sprintf('%s %d: %s', $this->Translate('Has Aux'), 2, $deviceConfiguration->hasAux2() ? $this->Translate('Yes') : $this->Translate('No')),
            sprintf('%s %d: %s', $this->Translate('Has Light'), 1, $deviceConfiguration->hasLight1() ? $this->Translate('Yes') : $this->Translate('No')),
            sprintf('%s %d: %s', $this->Translate('Has Light'), 2, $deviceConfiguration->hasLight2() ? $this->Translate('Yes') : $this->Translate('No')),
        ];

        $this->SendDebug(__FUNCTION__, 'txt=' . print_r($popupMessage, true), 0);
        $this->PopupMessage(implode(PHP_EOL, $popupMessage));
    }

    /**
     * @param int $pump
     *
     * @return false|void
     */
    private function TestTogglePump(int $pump)
    {
        if (!$this->CheckApiCallPrerequisites()) {
            return false;
        }

        $client = $this->getBalboaClient();
        $result = $client->deviceRequest(BalboaClient::TARGET_BUTTON, (string) BalboaClient::PUMP_BUTTON_MAP[$pump]);

        $this->handleLastRequest($client->getApi()->getLastRequest());
        $this->handleLastResponse($client->getApi()->getLastResponse());

        $popupMessage = [
            'Toggle Pump',
            $result ? 'Action successful' : 'Action not successful',
        ];

        $this->SendDebug(__FUNCTION__, 'txt=' . print_r($popupMessage, true), 0);
        $this->PopupMessage(implode(PHP_EOL, $popupMessage));
    }

    private function TestToggleBlower()
    {
        if (!$this->CheckApiCallPrerequisites()) {
            return false;
        }

        $client = $this->getBalboaClient();
        $result = $client->deviceRequest(BalboaClient::TARGET_BUTTON, (string) BalboaClient::BLOWER_BUTTON_MAP[1]);

        $this->handleLastRequest($client->getApi()->getLastRequest());
        $this->handleLastResponse($client->getApi()->getLastResponse());

        $popupMessage = [
            'Toggle Blower',
            $result ? 'Action successful' : 'Action not successful',
        ];

        $this->SendDebug(__FUNCTION__, 'txt=' . print_r($popupMessage, true), 0);
        $this->PopupMessage(implode(PHP_EOL, $popupMessage));
    }

    /**
     * @param int $light
     *
     * @return false|void
     */
    private function TestToggleLight(int $light)
    {
        if (!$this->CheckApiCallPrerequisites()) {
            return false;
        }

        $client = $this->getBalboaClient();
        $result = $client->deviceRequest(BalboaClient::TARGET_BUTTON, (string) BalboaClient::LIGHT_BUTTON_MAP[$light]);

        $this->handleLastRequest($client->getApi()->getLastRequest());
        $this->handleLastResponse($client->getApi()->getLastResponse());

        $popupMessage = [
            'Toggle Light',
            $result ? 'Action successful' : 'Action not successful',
        ];

        $this->SendDebug(__FUNCTION__, 'txt=' . print_r($popupMessage, true), 0);
        $this->PopupMessage(implode(PHP_EOL, $popupMessage));
    }

    /**
     * @param string $mode
     *
     * @return bool
     */
    private function SetHeatMode(string $mode): bool
    {
        if (!$this->CheckApiCallPrerequisites()) {
            return false;
        }

        $client = $this->getBalboaClient();
        $result = $client->setHeatMode($mode);

        $this->handleLastRequest($client->getApi()->getLastRequest());
        $this->handleLastResponse($client->getApi()->getLastResponse());

        $popupMessage = [
            sprintf('Set heat mode to "%s"', $mode),
            $result ? 'Heat mode successfully updated' : 'Could no update heat mode',
        ];

        $this->SendDebug(__FUNCTION__, 'txt=' . print_r($popupMessage, true), 0);

        return $result;
    }

    /**
     * @param string $range
     *
     * @return bool
     */
    private function SetTemperatureRange(string $range): bool
    {
        if (!$this->CheckApiCallPrerequisites()) {
            return false;
        }

        $client = $this->getBalboaClient();
        $result = $client->setTemperatureRange($range);

        $this->handleLastRequest($client->getApi()->getLastRequest());
        $this->handleLastResponse($client->getApi()->getLastResponse());

        $popupMessage = [
            sprintf('Set temperature range to "%s"', $range),
            $result ? 'Temperature range successfully updated' : 'Could no update temperature range',
        ];

        $this->SendDebug(__FUNCTION__, 'txt=' . print_r($popupMessage, true), 0);

        return $result;
    }

    /**
     * @param float $temperature
     *
     * @return bool
     */
    private function SetTargetTemperature(float $temperature): bool
    {
        if (!$this->CheckApiCallPrerequisites()) {
            return false;
        }

        $client = $this->getBalboaClient();
        $result = $client->setTargetTemperature($temperature);

        $this->handleLastRequest($client->getApi()->getLastRequest());
        $this->handleLastResponse($client->getApi()->getLastResponse());

        $popupMessage = [
            sprintf('Set target temperature to "%.1f"', $temperature),
            $result ? 'Target temperature successfully updated' : 'Could no update target temperature',
        ];

        $this->SendDebug(__FUNCTION__, 'txt=' . print_r($popupMessage, true), 0);

        return $result;
    }

    /**
     * @param string    $type
     * @param int       $number
     * @param bool|null $turnOn
     *
     * @return bool
     */
    private function SendButton(string $type, int $number, bool $turnOn = null): bool
    {
        if (!$this->CheckApiCallPrerequisites()) {
            return false;
        }

        if (!in_array($type, self::ALLOWED_BUTTON_TYPES)) {
            return false;
        }

        $targetValue = null;
        switch ($type) {
            case self::BUTTON_TYPE_PUMP:
                $targetValue = BalboaClient::PUMP_BUTTON_MAP[$number];
                break;
            case self::BUTTON_TYPE_BLOWER:
                $targetValue = BalboaClient::BLOWER_BUTTON_MAP[$number];
                break;
            case self::BUTTON_TYPE_AUX:
                $targetValue = BalboaClient::AUX_BUTTON_MAP[$number];
                break;
            case self::BUTTON_TYPE_LIGHT:
                $targetValue = BalboaClient::LIGHT_BUTTON_MAP[$number];
                break;
        }

        $action = null;
        if ($turnOn !== null) {
            $action = $turnOn ? PanelUpdate::STATE_ON : PanelUpdate::STATE_OFF;
        }

        $client = $this->getBalboaClient();
        $result = $client->deviceRequest(BalboaClient::TARGET_BUTTON, (string) $targetValue, $action);

        $this->handleLastRequest($client->getApi()->getLastRequest());
        $this->handleLastResponse($client->getApi()->getLastResponse());

        $popupMessage = [
            sprintf('Set %s %d to state %s', ucfirst($type), $number, $action === null ? 'toggle' : $action),
            $result ? 'Action successful' : 'Action not successful',
        ];

        $this->SendDebug(__FUNCTION__, 'txt=' . print_r($popupMessage, true), 0);

        return $result;
    }

    /**
     * Clear bearer token
     *
     * @return void
     */
    private function ClearAccessToken()
    {
        if (IPS_SemaphoreEnter($this->SemaphoreID, self::$semaphoreTime) == false) {
            $this->SendDebug(__FUNCTION__, 'unable to lock sempahore ' . $this->SemaphoreID, 0);
            return false;
        }
        $this->SetBuffer('AccessToken', '');
        IPS_SemaphoreLeave($this->SemaphoreID);
    }

    private function DisplayAccessToken()
    {
        if (IPS_SemaphoreEnter($this->SemaphoreID, self::$semaphoreTime) == false) {
            $this->SendDebug(__FUNCTION__, 'unable to lock sempahore ' . $this->SemaphoreID, 0);
            return false;
        }

        $accessToken = $this->GetBuffer('AccessToken');
        if (!empty($accessToken)) {
            $accessToken  = json_decode($accessToken, true);
            $popupMessage = [
                sprintf('Bearer Token: %s', $accessToken['bearerToken']),
                sprintf('Device ID: %s', $accessToken['deviceId']),
                sprintf('Expires at: %s', date('Y-m-d H:i:s', $accessToken['expiration'])),
            ];

            $this->PopupMessage(implode(PHP_EOL, $popupMessage));
        } else {
            $this->PopupMessage('No access token available.');
        }
    }

    private function GetDevices(): array
    {
        $client  = $this->getBalboaClient();
        $api     = $client->getApi();
        $devices = [
            [ 'id' => $api->getDeviceId(), 'name' => 'Balboa Whirlpool' ],
        ];

        return $devices;
    }

    /**
     * @return PanelUpdate|null
     */
    private function GetPanelUpdate(): ?PanelUpdate
    {
        $client       = $this->getBalboaClient();
        $panelUpdate  = $client->getPanelUpdate();
        $lastResponse = $client->getApi()->getLastResponse();

        $this->handleLastRequest($client->getApi()->getLastRequest());
        $this->handleLastResponse($lastResponse);

        if ($lastResponse->hasError() || $lastResponse->getHttpCode() !== 200 || ($panelUpdate instanceof PanelUpdate && $panelUpdate->getWifiStatus() !== PanelUpdate::WIFI_STATE_OK)) {
            return null;
        }

        return $panelUpdate;
    }

    /**
     * @return DeviceConfiguration|null
     */
    private function GetDeviceConfigutation(): ?DeviceConfiguration
    {
        $client              = $this->getBalboaClient();
        $deviceConfiguration = $client->getDeviceConfiguration();
        $lastResponse        = $client->getApi()->getLastResponse();

        $this->handleLastRequest($client->getApi()->getLastRequest());
        $this->handleLastResponse($lastResponse);

        if ($lastResponse->hasError() || $lastResponse->getHttpCode() !== 200) {
            return null;
        }

        return $deviceConfiguration;
    }

    /**
     * @param string      $targetName
     * @param string      $targetValue
     * @param string|null $valueAction
     *
     * @return bool
     */
    private function DeviceRequest(string $targetName, string $targetValue, string $valueAction = null): bool
    {
        $client = $this->getBalboaClient();
        $result = $client->deviceRequest($targetName, $targetValue, $valueAction);

        $this->handleLastRequest($client->getApi()->getLastRequest());
        $this->handleLastResponse($client->getApi()->getLastResponse());

        return $result;
    }

    /**
     * @return BalboaClient
     */
    private function getBalboaClient(): BalboaClient
    {
        if ($this->isTokenExpired()) {
            $this->SendDebug(__FUNCTION__, ' => token is expired, new authentication needed', 0);

            $this->ClearAccessToken();
        }

        $username    = $this->ReadPropertyString('username');
        $password    = $this->ReadPropertyString('password');
        $accessToken = $this->GetBuffer('AccessToken');
        $client      = BalboaClientFactory::createClient($username, $password);

        if (empty($accessToken)) {
            $this->SendDebug(__FUNCTION__, ' => begin authentication', 0);

            $api = $client->getApi();
            if ($api->authenticate()) {
                $this->SendDebug(__FUNCTION__, sprintf(' => authentication successful => bearer token: %s | device id: %s', $api->getBearerToken(), $api->getDeviceId()), 0);

                $expire    = time() + self::$tokenExpire;
                $tokenData = json_encode([
                    'bearerToken' => $api->getBearerToken(),
                    'deviceId'    => $api->getDeviceId(),
                    'expiration'  => $expire,
                ]);

                $this->SetBuffer('AccessToken', $tokenData);
            } else {
                $this->SendDebug(__FUNCTION__, ' => Login was not successful.', 0);
                $popupMessage = [
                    'Login to Balboa Backend failed!',
                ];
                $this->PopupMessage(implode(PHP_EOL, $popupMessage));

                $this->MaintainStatus(self::$IS_UNAUTHORIZED);
            }

            $this->handleLastRequest($api->getLastRequest());
            $this->handleLastResponse($api->getLastResponse());
        } else {
            $accessToken = json_decode($accessToken, true);
            $api         = $client->getApi();
            $api->setBearerToken($accessToken['bearerToken']);
            $api->setDeviceId($accessToken['deviceId']);
        }

        return $client;
    }

    /**
     * @return bool
     */
    private function isTokenExpired(): bool
    {
        $accessToken = $this->GetBuffer('AccessToken');
        if (empty($accessToken)) {
            return false;
        }
        $accessToken = json_decode($accessToken, true);

        return is_array($accessToken) && $accessToken['expiration'] <= time();
    }

    /**
     * @param BalboaApiRequest $request
     *
     * @return void
     */
    private function handleLastRequest(BalboaApiRequest $request)
    {
        $this->SendDebug(__FUNCTION__, 'http-' . $request->isPost() ? 'post' : 'get' . ': url=' . $request->getUrl(), 0);
        $this->SendDebug(__FUNCTION__, 'header=' . print_r($request->getHeaders(), true), 0);
        if (!empty($request->getPostData())) {
            $this->SendDebug(__FUNCTION__, '    postdata=' . $request->getPostData(), 0);
        }
    }

    /**
     * @param BalboaApiResponse $response
     *
     * @return void
     */
    private function handleLastResponse(BalboaApiResponse $response)
    {
        $this->SendDebug(__FUNCTION__, 'httpcode=' . $response->getHttpCode() . ', duration=' . $response->getDuration() . 's', 0);
        $this->SendDebug(__FUNCTION__, ' => error=' . $response->getError(), 0);
        $this->SendDebug(__FUNCTION__, ' => data=' . $response->getBody(), 0);

        $this->ApiCallCollect($response->getUrl(), $response->getError(), $response->getHttpCode());
    }

}
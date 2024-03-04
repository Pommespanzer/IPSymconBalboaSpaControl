<?php

declare(strict_types=1);

eval('
declare(strict_types=1);
namespace BalboaSpaControl {
?>'
    . preg_replace('/declare\(strict_types=1\);/', '', file_get_contents(__DIR__ . '/../libs/CommonStubs/common.php'))
    . '
}
');

require_once __DIR__ . '/balboa/models/DeviceConfiguration.php';
require_once __DIR__ . '/balboa/models/PanelUpdate.php';
require_once __DIR__ . '/balboa/BalboaApiRequest.php';
require_once __DIR__ . '/balboa/BalboaApiResponse.php';
require_once __DIR__ . '/balboa/BalboaApi.php';
require_once __DIR__ . '/balboa/BalboaClient.php';
require_once __DIR__ . '/balboa/BalboaClientFactory.php';

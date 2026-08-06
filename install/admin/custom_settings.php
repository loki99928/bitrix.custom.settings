<?php
$moduleAdminFile = $_SERVER['DOCUMENT_ROOT'] . '/local/modules/custom.settings/admin/custom_settings.php';

if (!file_exists($moduleAdminFile)) {
    $moduleAdminFile = $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/custom.settings/admin/custom_settings.php';
}

require $moduleAdminFile;

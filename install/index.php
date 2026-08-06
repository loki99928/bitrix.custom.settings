<?php
/**
 * Установщик модуля custom.settings
 */

use Bitrix\Main\Application;
use Bitrix\Main\Config\Option;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ModuleManager;

Loc::loadMessages(__FILE__);

class custom_settings extends CModule
{
    public $MODULE_ID = 'custom.settings';
    public $MODULE_VERSION;
    public $MODULE_VERSION_DATE;
    public $MODULE_NAME;
    public $MODULE_DESCRIPTION;
    public $PARTNER_NAME;
    public $PARTNER_URI;

    private $documentRoot;

    public function __construct()
    {
        $arModuleVersion = [];
        include __DIR__ . '/version.php';

        $this->MODULE_VERSION = $arModuleVersion['VERSION'];
        $this->MODULE_VERSION_DATE = $arModuleVersion['VERSION_DATE'];
        $this->MODULE_NAME = Loc::getMessage('CUSTOM_SETTINGS_MODULE_NAME');
        $this->MODULE_DESCRIPTION = Loc::getMessage('CUSTOM_SETTINGS_MODULE_DESCRIPTION');
        $this->PARTNER_NAME = Loc::getMessage('CUSTOM_SETTINGS_PARTNER_NAME');
        $this->PARTNER_URI = 'https://example.com';

        $this->documentRoot = Application::getDocumentRoot();
    }

    public function DoInstall()
    {
        global $APPLICATION;

        if (!$this->isVersionD7()) {
            $APPLICATION->ThrowException(Loc::getMessage('CUSTOM_SETTINGS_INSTALL_ERROR_VERSION'));
            return false;
        }

        ModuleManager::registerModule($this->MODULE_ID);
        $this->InstallFiles();

        $APPLICATION->IncludeAdminFile(
            Loc::getMessage('CUSTOM_SETTINGS_INSTALL_TITLE'),
            __DIR__ . '/step.php'
        );

        return true;
    }

    public function DoUninstall()
    {
        global $APPLICATION, $step;

        $step = (int)$step;

        if ($step < 2) {
            $APPLICATION->IncludeAdminFile(
                Loc::getMessage('CUSTOM_SETTINGS_UNINSTALL_TITLE'),
                __DIR__ . '/unstep1.php'
            );
        } elseif ($step === 2) {
            $this->UnInstallFiles();

            if (($_REQUEST['savedata'] ?? '') !== 'Y') {
                Option::delete($this->MODULE_ID);
            }

            ModuleManager::unRegisterModule($this->MODULE_ID);

            $APPLICATION->IncludeAdminFile(
                Loc::getMessage('CUSTOM_SETTINGS_UNINSTALL_TITLE'),
                __DIR__ . '/unstep2.php'
            );
        }

        return true;
    }

    public function InstallFiles()
    {
        CopyDirFiles(
            __DIR__ . '/admin',
            $this->documentRoot . '/bitrix/admin',
            true,
            true
        );

        return true;
    }

    public function UnInstallFiles()
    {
        DeleteDirFiles(
            __DIR__ . '/admin',
            $this->documentRoot . '/bitrix/admin'
        );

        return true;
    }

    private function isVersionD7(): bool
    {
        return CheckVersion(ModuleManager::getVersion('main'), '14.00.00');
    }
}

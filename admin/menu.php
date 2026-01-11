<?php
/**
 * Меню модуля в административной панели
 */

use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

$aMenu = [
    'parent_menu' => 'global_menu_settings',
    'section' => 'custom_settings',
    'sort' => 500,
    'text' => Loc::getMessage('CUSTOM_SETTINGS_MENU_TITLE'),
    'title' => Loc::getMessage('CUSTOM_SETTINGS_MENU_TITLE_FULL'),
    'icon' => 'custom_settings_menu_icon',
    'page_icon' => 'custom_settings_page_icon',
    'items_id' => 'menu_custom_settings',
    'items' => [
        [
            'text' => Loc::getMessage('CUSTOM_SETTINGS_MENU_ALL_SETTINGS'),
            'url' => 'custom_settings.php?lang=' . LANGUAGE_ID,
            'title' => Loc::getMessage('CUSTOM_SETTINGS_MENU_ALL_SETTINGS_TITLE'),
        ],
        [
            'text' => Loc::getMessage('CUSTOM_SETTINGS_MENU_MODULE_SETTINGS'),
            'url' => 'settings.php?lang=' . LANGUAGE_ID . '&mid=custom.settings',
            'title' => Loc::getMessage('CUSTOM_SETTINGS_MENU_MODULE_SETTINGS_TITLE'),
        ],
    ],
];

return $aMenu;

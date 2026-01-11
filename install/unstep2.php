<?php
if (!check_bitrix_sessid()) {
    return;
}

use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

CAdminMessage::ShowNote(Loc::getMessage('CUSTOM_SETTINGS_UNINSTALL_SUCCESS'));
?>
<form action="<?= $APPLICATION->GetCurPage() ?>">
    <input type="hidden" name="lang" value="<?= LANG ?>">
    <input type="submit" value="<?= Loc::getMessage('CUSTOM_SETTINGS_INSTALL_BACK') ?>">
</form>

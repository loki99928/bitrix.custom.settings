<?php
if (!check_bitrix_sessid()) {
    return;
}

use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

if ($exception = $APPLICATION->GetException()) {
    CAdminMessage::ShowMessage([
        'TYPE' => 'ERROR',
        'MESSAGE' => Loc::getMessage('CUSTOM_SETTINGS_INSTALL_ERROR'),
        'DETAILS' => $exception->GetString(),
        'HTML' => true,
    ]);
} else {
    CAdminMessage::ShowNote(Loc::getMessage('CUSTOM_SETTINGS_INSTALL_SUCCESS'));
}
?>
<form action="<?= $APPLICATION->GetCurPage() ?>">
    <input type="hidden" name="lang" value="<?= LANG ?>">
    <input type="submit" value="<?= Loc::getMessage('CUSTOM_SETTINGS_INSTALL_BACK') ?>">
</form>

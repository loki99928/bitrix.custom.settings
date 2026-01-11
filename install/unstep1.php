<?php
if (!check_bitrix_sessid()) {
    return;
}

use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);
?>
<form action="<?= $APPLICATION->GetCurPage() ?>">
    <?= bitrix_sessid_post() ?>
    <input type="hidden" name="lang" value="<?= LANG ?>">
    <input type="hidden" name="id" value="custom.settings">
    <input type="hidden" name="uninstall" value="Y">
    <input type="hidden" name="step" value="2">

    <p><?= Loc::getMessage('CUSTOM_SETTINGS_UNINSTALL_WARNING') ?></p>

    <p>
        <input type="checkbox" name="savedata" id="savedata" value="Y" checked>
        <label for="savedata"><?= Loc::getMessage('CUSTOM_SETTINGS_UNINSTALL_SAVE_DATA') ?></label>
    </p>

    <input type="submit" value="<?= Loc::getMessage('CUSTOM_SETTINGS_UNINSTALL_DELETE') ?>">
</form>

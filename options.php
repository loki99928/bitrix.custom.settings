<?php
/**
 * Страница настроек модуля - только права доступа
 */

use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

$module_id = 'custom.settings';
$RIGHT = $APPLICATION->GetGroupRight($module_id);

if ($RIGHT < 'R') {
    $APPLICATION->AuthForm(Loc::getMessage('ACCESS_DENIED'));
}

$aTabs = [
    [
        'DIV' => 'edit1',
        'TAB' => Loc::getMessage('CUSTOM_SETTINGS_TAB_SETTINGS'),
        'ICON' => 'custom_settings',
        'TITLE' => Loc::getMessage('CUSTOM_SETTINGS_TAB_SETTINGS_TITLE'),
    ],
    [
        'DIV' => 'edit2',
        'TAB' => Loc::getMessage('CUSTOM_SETTINGS_TAB_PERMISSIONS'),
        'ICON' => 'custom_settings',
        'TITLE' => Loc::getMessage('CUSTOM_SETTINGS_TAB_PERMISSIONS_TITLE'),
    ],
];

$tabControl = new CAdminTabControl('tabControl', $aTabs);

// Сохранение прав доступа
if ($REQUEST_METHOD === 'POST' && $RIGHT >= 'W' && check_bitrix_sessid()) {
    if (isset($_POST['GROUPS'])) {
        foreach ($_POST['GROUPS'] as $groupId => $right) {
            $APPLICATION->SetGroupRight($module_id, $groupId, $right);
        }
    }

    if (isset($_POST['apply'])) {
        LocalRedirect($APPLICATION->GetCurPage() . '?mid=' . urlencode($module_id) . '&lang=' . LANGUAGE_ID . '&' . $tabControl->ActiveTabParam());
    } else {
        LocalRedirect($APPLICATION->GetCurPage() . '?mid=' . urlencode($module_id) . '&lang=' . LANGUAGE_ID);
    }
}

$tabControl->Begin();
?>

<form method="POST" action="<?= $APPLICATION->GetCurPage() ?>?mid=<?= urlencode($module_id) ?>&lang=<?= LANGUAGE_ID ?>">
    <?= bitrix_sessid_post() ?>

    <?php $tabControl->BeginNextTab(); ?>
    <tr>
        <td colspan="2">
            <p><?= Loc::getMessage('CUSTOM_SETTINGS_MANAGE_INFO') ?></p>
            <p>
                <a href="custom_settings.php?lang=<?= LANGUAGE_ID ?>" class="adm-btn">
                    <?= Loc::getMessage('CUSTOM_SETTINGS_MANAGE_LINK') ?>
                </a>
            </p>
        </td>
    </tr>

    <?php
    $tabControl->BeginNextTab();
    require_once $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/admin/group_rights.php';
    ?>

    <?php $tabControl->Buttons(); ?>

    <input type="submit" name="apply" value="<?= Loc::getMessage('CUSTOM_SETTINGS_BTN_APPLY') ?>" class="adm-btn-save">
    <input type="submit" name="save" value="<?= Loc::getMessage('CUSTOM_SETTINGS_BTN_SAVE') ?>">

</form>

<?php
$tabControl->End();

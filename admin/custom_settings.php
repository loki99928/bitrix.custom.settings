<?php
/**
 * Административная страница управления настройками с переключаемыми вкладками
 */

require_once $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_admin_before.php';

use Bitrix\Main\Config\Option;
use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

$module_id = 'custom.settings';
$DESC_SUFFIX = '__desc';
$TAB_SUFFIX = '__tab';
$TYPE_SUFFIX = '__type';
$VARIANTS_SUFFIX = '__variants';
$TABS_KEY = '__tabs';

$FIELD_TYPES = [
    'text' => Loc::getMessage('CUSTOM_SETTINGS_TYPE_TEXT'),
    'textarea' => Loc::getMessage('CUSTOM_SETTINGS_TYPE_TEXTAREA'),
    'checkbox' => Loc::getMessage('CUSTOM_SETTINGS_TYPE_CHECKBOX'),
    'number' => Loc::getMessage('CUSTOM_SETTINGS_TYPE_NUMBER'),
    'password' => Loc::getMessage('CUSTOM_SETTINGS_TYPE_PASSWORD'),
    'select' => Loc::getMessage('CUSTOM_SETTINGS_TYPE_SELECT'),
    'image' => Loc::getMessage('CUSTOM_SETTINGS_TYPE_IMAGE'),
];

$RIGHT = $APPLICATION->GetGroupRight($module_id);
if ($RIGHT < 'R') {
    $APPLICATION->AuthForm(Loc::getMessage('ACCESS_DENIED'));
}

function getTabs($module_id, $TABS_KEY)
{
    $tabsJson = Option::get($module_id, $TABS_KEY, '[]');
    $tabs = json_decode($tabsJson, true);
    if (!is_array($tabs)) {
        $tabs = [];
    }
    usort($tabs, function ($a, $b) {
        return ($a['sort'] ?? 500) - ($b['sort'] ?? 500);
    });
    return $tabs;
}

function saveTabs($module_id, $TABS_KEY, $tabs)
{
    Option::set($module_id, $TABS_KEY, json_encode($tabs, JSON_UNESCAPED_UNICODE));
}

function normalizeFieldType($type, array $FIELD_TYPES)
{
    $type = (string)$type;
    return isset($FIELD_TYPES[$type]) ? $type : 'textarea';
}

function normalizeOptionValue($type, $value)
{
    if ($type === 'checkbox') {
        return ($value === 'Y' || $value === '1' || $value === 1 || $value === true) ? 'Y' : 'N';
    }

    if ($type === 'number') {
        $value = trim((string)$value);
        return $value === '' ? '' : (string)$value;
    }

    return (string)$value;
}

function formatOptionValueForDisplay($type, $value)
{
    switch ($type) {
        case 'checkbox':
            return $value === 'Y'
                ? Loc::getMessage('CUSTOM_SETTINGS_CHECKBOX_YES')
                : Loc::getMessage('CUSTOM_SETTINGS_CHECKBOX_NO');
        case 'password':
            return $value !== '' ? '••••••••' : '';
        default:
            return $value;
    }
}

function saveSettingsImage($moduleId, $optionName)
{
    $currentId = (int)Option::get($moduleId, $optionName, '0');
    $delete = (($_POST['option_image_delete'] ?? '') === 'Y');
    $file = $_FILES['option_image'] ?? null;

    if (is_array($file) && (int)($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
        if ((int)$file['error'] !== UPLOAD_ERR_OK) {
            return ['error' => Loc::getMessage('CUSTOM_SETTINGS_IMAGE_SAVE_ERROR'), 'value' => $currentId > 0 ? (string)$currentId : ''];
        }

        $check = CFile::CheckImageFile($file, 0, 0, 0, ['jpg', 'jpeg', 'png', 'gif', 'webp']);
        if ($check !== '') {
            return ['error' => $check, 'value' => $currentId > 0 ? (string)$currentId : ''];
        }

        $file['MODULE_ID'] = $moduleId;
        $fileId = (int)CFile::SaveFile($file, 'custom.settings');
        if ($fileId <= 0) {
            return ['error' => Loc::getMessage('CUSTOM_SETTINGS_IMAGE_SAVE_ERROR'), 'value' => $currentId > 0 ? (string)$currentId : ''];
        }

        if ($currentId > 0 && $currentId !== $fileId) {
            CFile::Delete($currentId);
        }

        return ['error' => '', 'value' => (string)$fileId];
    }

    if ($delete && $currentId > 0) {
        CFile::Delete($currentId);
        return ['error' => '', 'value' => ''];
    }

    return ['error' => '', 'value' => $currentId > 0 ? (string)$currentId : ''];
}

function deleteSettingsImage($moduleId, $optionName)
{
    if (Option::get($moduleId, $optionName . '__type', '') !== 'image') {
        return;
    }

    $fileId = (int)Option::get($moduleId, $optionName, '0');
    if ($fileId > 0) {
        CFile::Delete($fileId);
    }
}

function parseSelectVariants($variantsRaw)
{
    $parts = preg_split('/[\r\n;]+/', (string)$variantsRaw);
    $variants = [];
    foreach ($parts as $part) {
        $part = trim($part);
        if ($part !== '') {
            $variants[] = $part;
        }
    }
    return array_values(array_unique($variants));
}

// Обработка действий
$action = $_REQUEST['action'] ?? '';
$activeTab = $_REQUEST['active_tab'] ?? '';
$saveError = '';

if ($REQUEST_METHOD === 'POST' && check_bitrix_sessid() && $RIGHT >= 'W') {
    switch ($action) {
        case 'save':
            $optionName = trim($_POST['option_name'] ?? '');
            $optionType = normalizeFieldType($_POST['option_type'] ?? 'textarea', $FIELD_TYPES);
            $rawValue = $_POST['option_value'] ?? '';
            if (is_array($rawValue)) {
                $rawValue = end($rawValue);
            }
            $optionValue = normalizeOptionValue($optionType, $rawValue);
            $optionDesc = trim($_POST['option_desc'] ?? '');
            $optionTab = trim($_POST['option_tab'] ?? '');
            $optionVariants = trim($_POST['option_variants'] ?? '');

            if ($optionType === 'select') {
                $variants = parseSelectVariants($optionVariants);
                $optionVariants = implode("\n", $variants);
                if ($optionValue !== '' && !in_array($optionValue, $variants, true) && !empty($variants)) {
                    $optionValue = $variants[0];
                }
            }

            if (!empty($optionName) && strpos($optionName, '__') === false) {
                $previousType = Option::get($module_id, $optionName . $TYPE_SUFFIX, '');

                if ($optionType === 'image') {
                    $imageResult = saveSettingsImage($module_id, $optionName);
                    if ($imageResult['error'] !== '') {
                        $saveError = $imageResult['error'];
                        break;
                    }
                    $optionValue = $imageResult['value'];
                } elseif ($previousType === 'image') {
                    deleteSettingsImage($module_id, $optionName);
                }

                Option::set($module_id, $optionName, $optionValue);
                Option::set($module_id, $optionName . $DESC_SUFFIX, $optionDesc);
                Option::set($module_id, $optionName . $TAB_SUFFIX, $optionTab);
                Option::set($module_id, $optionName . $TYPE_SUFFIX, $optionType);
                if ($optionType === 'select') {
                    Option::set($module_id, $optionName . $VARIANTS_SUFFIX, $optionVariants);
                } else {
                    Option::delete($module_id, ['name' => $optionName . $VARIANTS_SUFFIX]);
                }
                LocalRedirect($APPLICATION->GetCurPage() . '?saved=Y&active_tab=' . urlencode($optionTab ?: '__no_tab'));
            }
            break;

        case 'delete':
            $optionName = $_POST['option_name'] ?? '';
            $returnTab = $_POST['return_tab'] ?? '';
            if (!empty($optionName)) {
                deleteSettingsImage($module_id, $optionName);
                Option::delete($module_id, ['name' => $optionName]);
                Option::delete($module_id, ['name' => $optionName . $DESC_SUFFIX]);
                Option::delete($module_id, ['name' => $optionName . $TAB_SUFFIX]);
                Option::delete($module_id, ['name' => $optionName . $TYPE_SUFFIX]);
                Option::delete($module_id, ['name' => $optionName . $VARIANTS_SUFFIX]);
                LocalRedirect($APPLICATION->GetCurPage() . '?deleted=Y&active_tab=' . urlencode($returnTab));
            }
            break;

        case 'save_tab':
            $tabId = trim($_POST['tab_id'] ?? '');
            $tabName = trim($_POST['tab_name'] ?? '');
            $tabSort = (int)($_POST['tab_sort'] ?? 500);
            $isNew = ($_POST['is_new_tab'] ?? '') === 'Y';

            if (!empty($tabName)) {
                $tabs = getTabs($module_id, $TABS_KEY);

                if ($isNew) {
                    $tabId = 'tab_' . time();
                    $tabs[] = ['id' => $tabId, 'name' => $tabName, 'sort' => $tabSort];
                } else {
                    foreach ($tabs as &$tab) {
                        if ($tab['id'] === $tabId) {
                            $tab['name'] = $tabName;
                            $tab['sort'] = $tabSort;
                            break;
                        }
                    }
                    unset($tab);
                }

                saveTabs($module_id, $TABS_KEY, $tabs);
                LocalRedirect($APPLICATION->GetCurPage() . '?tab_saved=Y&active_tab=__manage');
            }
            break;

        case 'delete_tab':
            $tabId = $_POST['tab_id'] ?? '';
            if (!empty($tabId)) {
                $tabs = getTabs($module_id, $TABS_KEY);
                $tabs = array_filter($tabs, function ($tab) use ($tabId) {
                    return $tab['id'] !== $tabId;
                });
                saveTabs($module_id, $TABS_KEY, array_values($tabs));

                $allOptions = Option::getForModule($module_id);
                foreach ($allOptions as $name => $value) {
                    if (substr($name, -strlen($TAB_SUFFIX)) === $TAB_SUFFIX && $value === $tabId) {
                        Option::set($module_id, $name, '');
                    }
                }

                LocalRedirect($APPLICATION->GetCurPage() . '?tab_deleted=Y&active_tab=__manage');
            }
            break;
    }
}

require_once $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_admin_after.php';

$APPLICATION->SetTitle(Loc::getMessage('CUSTOM_SETTINGS_PAGE_TITLE'));

$aMenu = [
    [
        'TEXT' => Loc::getMessage('CUSTOM_SETTINGS_ADD_NEW'),
        'LINK' => 'javascript:void(0)',
        'ONCLICK' => 'showAddForm()',
        'ICON' => 'btn_new',
    ],
    [
        'TEXT' => Loc::getMessage('CUSTOM_SETTINGS_MODULE_OPTIONS'),
        'LINK' => 'settings.php?lang=' . LANGUAGE_ID . '&mid=' . $module_id,
        'ICON' => 'btn_settings',
    ],
];

$context = new CAdminContextMenu($aMenu);
$context->Show();

if (($_GET['saved'] ?? '') === 'Y') {
    CAdminMessage::ShowNote(Loc::getMessage('CUSTOM_SETTINGS_SAVED'));
}
if (($_GET['deleted'] ?? '') === 'Y') {
    CAdminMessage::ShowNote(Loc::getMessage('CUSTOM_SETTINGS_DELETED'));
}
if (($_GET['tab_saved'] ?? '') === 'Y') {
    CAdminMessage::ShowNote(Loc::getMessage('CUSTOM_SETTINGS_TAB_SAVED'));
}
if (($_GET['tab_deleted'] ?? '') === 'Y') {
    CAdminMessage::ShowNote(Loc::getMessage('CUSTOM_SETTINGS_TAB_DELETED'));
}
if ($saveError !== '') {
    CAdminMessage::ShowMessage($saveError);
}

$tabs = getTabs($module_id, $TABS_KEY);

$allOptionsRaw = Option::getForModule($module_id);
$allOptions = [];

foreach ($allOptionsRaw as $name => $value) {
    if (strpos($name, '__') !== false) {
        continue;
    }

    $type = normalizeFieldType($allOptionsRaw[$name . $TYPE_SUFFIX] ?? 'textarea', $FIELD_TYPES);
    $allOptions[$name] = [
        'value' => $value,
        'desc' => $allOptionsRaw[$name . $DESC_SUFFIX] ?? '',
        'tab' => $allOptionsRaw[$name . $TAB_SUFFIX] ?? '',
        'type' => $type,
        'variants' => $allOptionsRaw[$name . $VARIANTS_SUFFIX] ?? '',
    ];
}

$optionsByTab = ['__no_tab' => []];
foreach ($tabs as $tab) {
    $optionsByTab[$tab['id']] = [];
}

foreach ($allOptions as $name => $data) {
    $tabId = $data['tab'] ?: '__no_tab';
    if (!isset($optionsByTab[$tabId])) {
        $tabId = '__no_tab';
    }
    $optionsByTab[$tabId][$name] = $data;
}

if ($activeTab === '') {
    if (!empty($tabs)) {
        $activeTab = $tabs[0]['id'];
    } else {
        $activeTab = '__no_tab';
    }
}
?>

<style>
    .custom-tabs { display: flex; flex-wrap: wrap; border-bottom: 1px solid #e0e8ea; margin-bottom: 0; padding: 0; list-style: none; background: #f9f9f9; }
    .custom-tabs li { margin: 0; }
    .custom-tabs li a { display: block; padding: 12px 20px; text-decoration: none; color: #333; border: 1px solid transparent; border-bottom: none; margin-bottom: -1px; background: transparent; transition: background 0.2s; }
    .custom-tabs li a:hover { background: #fff; }
    .custom-tabs li.active a { background: #fff; border-color: #e0e8ea; border-bottom-color: #fff; font-weight: bold; color: #000; }
    .custom-tabs li.tab-manage a { color: #666; font-style: italic; }

    .tab-content { display: none; padding: 20px; background: #fff; border: 1px solid #e0e8ea; border-top: none; }
    .tab-content.active { display: block; }

    .settings-table { width: 100%; border-collapse: collapse; }
    .settings-table th, .settings-table td { border: 1px solid #e0e8ea; padding: 10px; text-align: left; vertical-align: top; }
    .settings-table th { background: #f5f9f9; font-weight: bold; }
    .settings-table tr:hover { background: #f9fcfc; }

    .add-form { display: none; background: #f5f9f9; padding: 20px; margin-bottom: 20px; border: 1px solid #e0e8ea; border-radius: 4px; }
    .add-form.visible { display: block; }
    .form-row { margin-bottom: 15px; }
    .form-row label { display: block; margin-bottom: 5px; font-weight: bold; }
    .form-row input[type="text"],
    .form-row input[type="number"],
    .form-row input[type="password"],
    .form-row textarea,
    .form-row select { width: 100%; max-width: 500px; border: 1px solid #c8d3d5; border-radius: 3px; box-sizing: border-box; padding: 6px 8px; }
    .form-row textarea { min-height: 80px; }
    .form-row small { color: #666; display: block; margin-top: 4px; }
    .form-row-inline { display: flex; gap: 10px; align-items: flex-end; flex-wrap: wrap; }
    .form-row-inline .form-row { margin-bottom: 0; flex: 1; min-width: 200px; }
    .form-row-inline .form-row.narrow { flex: 0 0 100px; min-width: 100px; }
    .form-row-inline .form-row.btn-row { flex: 0 0 auto; min-width: auto; }

    .btn-action { padding: 5px 10px; margin-right: 5px; cursor: pointer; }
    .option-value { max-width: 350px; word-break: break-word; }
    .option-desc { color: #666; font-size: 12px; max-width: 200px; }
    .option-type { color: #666; font-size: 12px; white-space: nowrap; }

    .tabs-list { margin-top: 20px; }
    .tabs-list table { width: 100%; border-collapse: collapse; }
    .tabs-list th, .tabs-list td { border: 1px solid #e0e8ea; padding: 8px; text-align: left; }
    .tabs-list th { background: #f5f9f9; }

    .no-options { color: #999; padding: 20px; text-align: center; }
    .tab-add-form { background: #fff; padding: 15px; border: 1px solid #e0e8ea; border-radius: 4px; margin-bottom: 20px; }
    .checkbox-value-wrap { display: flex; align-items: center; gap: 8px; }
    .image-preview img,
    .settings-table .option-image { display: block; max-width: 160px; max-height: 70px; }

    .usage-help { background: #fff; border: 1px solid #e0e8ea; border-radius: 4px; margin-bottom: 20px; }
    .usage-help-toggle { display: flex; align-items: center; justify-content: space-between; width: 100%; padding: 12px 16px; border: 0; background: #f5f9f9; cursor: pointer; text-align: left; font-size: 14px; font-weight: bold; color: #333; box-sizing: border-box; }
    .usage-help-toggle:hover { background: #eef5f5; }
    .usage-help-toggle .usage-help-arrow { color: #666; font-weight: normal; }
    .usage-help-body { display: none; padding: 16px 20px 20px; border-top: 1px solid #e0e8ea; }
    .usage-help.open .usage-help-body { display: block; }
    .usage-help-body p { margin: 0 0 10px; color: #444; line-height: 1.45; }
    .usage-help-body ul { margin: 0 0 14px; padding-left: 18px; color: #444; line-height: 1.5; }
    .usage-help-body code, .usage-help-body pre { font-family: Consolas, Monaco, monospace; }
    .usage-help-body code { background: #f0f4f5; padding: 1px 5px; border-radius: 2px; }
    .usage-help-body pre { background: #f5f9f9; border: 1px solid #e0e8ea; border-radius: 3px; padding: 12px 14px; overflow: auto; margin: 0 0 14px; font-size: 12px; line-height: 1.5; white-space: pre; }
    .usage-help-body h4 { margin: 16px 0 8px; font-size: 13px; }
    .usage-help-body h4:first-child { margin-top: 0; }
</style>

<div class="usage-help" id="usageHelp">
    <button type="button" class="usage-help-toggle" onclick="toggleUsageHelp()">
        <span><?= Loc::getMessage('CUSTOM_SETTINGS_USAGE_TITLE') ?></span>
        <span class="usage-help-arrow" id="usageHelpArrow">▼</span>
    </button>
    <div class="usage-help-body">
        <p><?= Loc::getMessage('CUSTOM_SETTINGS_USAGE_INTRO') ?></p>

        <h4><?= Loc::getMessage('CUSTOM_SETTINGS_USAGE_BASIC_TITLE') ?></h4>
        <pre><?= htmlspecialchars(Loc::getMessage('CUSTOM_SETTINGS_USAGE_BASIC_CODE')) ?></pre>

        <h4><?= Loc::getMessage('CUSTOM_SETTINGS_USAGE_TYPES_TITLE') ?></h4>
        <ul>
            <li><?= Loc::getMessage('CUSTOM_SETTINGS_USAGE_TYPE_TEXT') ?></li>
            <li><?= Loc::getMessage('CUSTOM_SETTINGS_USAGE_TYPE_CHECKBOX') ?></li>
            <li><?= Loc::getMessage('CUSTOM_SETTINGS_USAGE_TYPE_NUMBER') ?></li>
            <li><?= Loc::getMessage('CUSTOM_SETTINGS_USAGE_TYPE_SELECT') ?></li>
            <li><?= Loc::getMessage('CUSTOM_SETTINGS_USAGE_TYPE_IMAGE') ?></li>
        </ul>
        <pre><?= htmlspecialchars(Loc::getMessage('CUSTOM_SETTINGS_USAGE_TYPES_CODE')) ?></pre>

        <h4><?= Loc::getMessage('CUSTOM_SETTINGS_USAGE_TEMPLATE_TITLE') ?></h4>
        <p><?= Loc::getMessage('CUSTOM_SETTINGS_USAGE_TEMPLATE_NOTE') ?></p>
        <pre><?= htmlspecialchars(Loc::getMessage('CUSTOM_SETTINGS_USAGE_TEMPLATE_CODE')) ?></pre>

        <h4><?= Loc::getMessage('CUSTOM_SETTINGS_USAGE_ALL_TITLE') ?></h4>
        <pre><?= htmlspecialchars(Loc::getMessage('CUSTOM_SETTINGS_USAGE_ALL_CODE')) ?></pre>

        <p><?= Loc::getMessage('CUSTOM_SETTINGS_USAGE_NOTE') ?></p>
    </div>
</div>

<!-- Форма добавления настройки -->
<div id="addForm" class="add-form">
    <h3 id="addFormTitle"><?= Loc::getMessage('CUSTOM_SETTINGS_ADD_OPTION') ?></h3>
    <form method="POST" action="<?= $APPLICATION->GetCurPage() ?>" id="optionForm" enctype="multipart/form-data">
        <?= bitrix_sessid_post() ?>
        <input type="hidden" name="action" value="save">

        <div class="form-row">
            <label for="option_name"><?= Loc::getMessage('CUSTOM_SETTINGS_OPTION_NAME') ?>:</label>
            <input type="text" id="option_name" name="option_name" required
                   placeholder="<?= Loc::getMessage('CUSTOM_SETTINGS_OPTION_NAME_HINT') ?>">
            <small><?= Loc::getMessage('CUSTOM_SETTINGS_OPTION_NAME_NOTE') ?></small>
        </div>

        <div class="form-row">
            <label for="option_type"><?= Loc::getMessage('CUSTOM_SETTINGS_OPTION_TYPE') ?>:</label>
            <select id="option_type" name="option_type" onchange="onTypeChange()">
                <?php foreach ($FIELD_TYPES as $typeCode => $typeName): ?>
                    <option value="<?= htmlspecialchars($typeCode) ?>"><?= htmlspecialchars($typeName) ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-row" id="variantsRow" style="display:none">
            <label for="option_variants"><?= Loc::getMessage('CUSTOM_SETTINGS_OPTION_VARIANTS') ?>:</label>
            <textarea id="option_variants" name="option_variants"
                      placeholder="<?= Loc::getMessage('CUSTOM_SETTINGS_OPTION_VARIANTS_HINT') ?>"></textarea>
            <small><?= Loc::getMessage('CUSTOM_SETTINGS_OPTION_VARIANTS_NOTE') ?></small>
        </div>

        <div class="form-row">
            <label for="option_tab"><?= Loc::getMessage('CUSTOM_SETTINGS_OPTION_TAB') ?>:</label>
            <select id="option_tab" name="option_tab">
                <option value=""><?= Loc::getMessage('CUSTOM_SETTINGS_NO_TAB') ?></option>
                <?php foreach ($tabs as $tab): ?>
                    <option value="<?= htmlspecialchars($tab['id']) ?>"><?= htmlspecialchars($tab['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-row">
            <label for="option_desc"><?= Loc::getMessage('CUSTOM_SETTINGS_OPTION_DESC') ?>:</label>
            <input type="text" id="option_desc" name="option_desc"
                   placeholder="<?= Loc::getMessage('CUSTOM_SETTINGS_OPTION_DESC_HINT') ?>">
        </div>

        <div class="form-row" id="valueRow">
            <label for="option_value"><?= Loc::getMessage('CUSTOM_SETTINGS_OPTION_VALUE') ?>:</label>
            <div id="valueControl">
                <textarea id="option_value" name="option_value"
                          placeholder="<?= Loc::getMessage('CUSTOM_SETTINGS_OPTION_VALUE_HINT') ?>"></textarea>
            </div>
        </div>

        <div class="form-row">
            <input type="submit" value="<?= Loc::getMessage('CUSTOM_SETTINGS_BTN_SAVE') ?>" class="adm-btn-save">
            <input type="button" value="<?= Loc::getMessage('CUSTOM_SETTINGS_BTN_CANCEL') ?>" onclick="hideAddForm()">
        </div>
    </form>
</div>

<!-- Вкладки -->
<ul class="custom-tabs">
    <?php foreach ($tabs as $tab): ?>
        <li class="<?= $activeTab === $tab['id'] ? 'active' : '' ?>">
            <a href="javascript:void(0)" onclick="switchTab('<?= htmlspecialchars($tab['id']) ?>')"><?= htmlspecialchars($tab['name']) ?></a>
        </li>
    <?php endforeach; ?>
    <li class="<?= $activeTab === '__no_tab' ? 'active' : '' ?>">
        <a href="javascript:void(0)" onclick="switchTab('__no_tab')"><?= Loc::getMessage('CUSTOM_SETTINGS_NO_TAB_TITLE') ?></a>
    </li>
    <li class="tab-manage <?= $activeTab === '__manage' ? 'active' : '' ?>">
        <a href="javascript:void(0)" onclick="switchTab('__manage')">⚙ <?= Loc::getMessage('CUSTOM_SETTINGS_MANAGE_TABS') ?></a>
    </li>
</ul>

<?php foreach ($tabs as $tab): ?>
    <div id="tab-<?= htmlspecialchars($tab['id']) ?>" class="tab-content <?= $activeTab === $tab['id'] ? 'active' : '' ?>">
        <?php if (!empty($optionsByTab[$tab['id']])): ?>
            <?php renderOptionsTable($optionsByTab[$tab['id']], $FIELD_TYPES, $tab['id']); ?>
        <?php else: ?>
            <div class="no-options"><?= Loc::getMessage('CUSTOM_SETTINGS_TAB_EMPTY') ?></div>
        <?php endif; ?>
    </div>
<?php endforeach; ?>

<div id="tab-__no_tab" class="tab-content <?= $activeTab === '__no_tab' ? 'active' : '' ?>">
    <?php if (!empty($optionsByTab['__no_tab'])): ?>
        <?php renderOptionsTable($optionsByTab['__no_tab'], $FIELD_TYPES, '__no_tab'); ?>
    <?php else: ?>
        <div class="no-options"><?= Loc::getMessage('CUSTOM_SETTINGS_TAB_EMPTY') ?></div>
    <?php endif; ?>
</div>

<div id="tab-__manage" class="tab-content <?= $activeTab === '__manage' ? 'active' : '' ?>">
    <div class="tab-add-form">
        <h4><?= Loc::getMessage('CUSTOM_SETTINGS_ADD_TAB_TITLE') ?></h4>
        <form method="POST" action="<?= $APPLICATION->GetCurPage() ?>">
            <?= bitrix_sessid_post() ?>
            <input type="hidden" name="action" value="save_tab">
            <input type="hidden" name="is_new_tab" id="is_new_tab" value="Y">
            <input type="hidden" name="tab_id" id="edit_tab_id" value="">

            <div class="form-row-inline">
                <div class="form-row">
                    <label for="tab_name"><?= Loc::getMessage('CUSTOM_SETTINGS_TAB_NAME') ?>:</label>
                    <input type="text" id="tab_name" name="tab_name" required placeholder="<?= Loc::getMessage('CUSTOM_SETTINGS_TAB_NAME_HINT') ?>">
                </div>
                <div class="form-row narrow">
                    <label for="tab_sort"><?= Loc::getMessage('CUSTOM_SETTINGS_TAB_SORT') ?>:</label>
                    <input type="text" id="tab_sort" name="tab_sort" value="500">
                </div>
                <div class="form-row btn-row">
                    <label>&nbsp;</label>
                    <input type="submit" value="<?= Loc::getMessage('CUSTOM_SETTINGS_BTN_ADD_TAB') ?>" id="btnAddTab" class="adm-btn-save">
                    <input type="button" value="<?= Loc::getMessage('CUSTOM_SETTINGS_BTN_CANCEL') ?>" onclick="resetTabForm()" style="display:none" id="btnCancelTab">
                </div>
            </div>
        </form>
    </div>

    <?php if (!empty($tabs)): ?>
        <div class="tabs-list">
            <table>
                <thead>
                <tr>
                    <th><?= Loc::getMessage('CUSTOM_SETTINGS_TAB_NAME') ?></th>
                    <th width="100"><?= Loc::getMessage('CUSTOM_SETTINGS_TAB_SORT') ?></th>
                    <th width="100"><?= Loc::getMessage('CUSTOM_SETTINGS_TAB_COUNT') ?></th>
                    <th width="180"><?= Loc::getMessage('CUSTOM_SETTINGS_COL_ACTIONS') ?></th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($tabs as $tab): ?>
                    <tr>
                        <td><strong><?= htmlspecialchars($tab['name']) ?></strong></td>
                        <td><?= (int)$tab['sort'] ?></td>
                        <td><?= count($optionsByTab[$tab['id']] ?? []) ?></td>
                        <td>
                            <button type="button" class="btn-action" onclick="editTab('<?= htmlspecialchars($tab['id'], ENT_QUOTES) ?>', '<?= htmlspecialchars($tab['name'], ENT_QUOTES) ?>', <?= (int)$tab['sort'] ?>)">
                                <?= Loc::getMessage('CUSTOM_SETTINGS_BTN_EDIT') ?>
                            </button>
                            <form method="POST" style="display:inline" onsubmit="return confirm('<?= Loc::getMessage('CUSTOM_SETTINGS_CONFIRM_DELETE_TAB') ?>')">
                                <?= bitrix_sessid_post() ?>
                                <input type="hidden" name="action" value="delete_tab">
                                <input type="hidden" name="tab_id" value="<?= htmlspecialchars($tab['id']) ?>">
                                <button type="submit" class="btn-action"><?= Loc::getMessage('CUSTOM_SETTINGS_BTN_DELETE') ?></button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <div class="no-options"><?= Loc::getMessage('CUSTOM_SETTINGS_NO_TABS') ?></div>
    <?php endif; ?>
</div>

<?php
function renderOptionsTable($options, $FIELD_TYPES, $currentTab)
{
    ?>
    <table class="settings-table">
        <thead>
        <tr>
            <th width="16%"><?= Loc::getMessage('CUSTOM_SETTINGS_COL_NAME') ?></th>
            <th width="12%"><?= Loc::getMessage('CUSTOM_SETTINGS_COL_TYPE') ?></th>
            <th width="18%"><?= Loc::getMessage('CUSTOM_SETTINGS_COL_DESC') ?></th>
            <th width="34%"><?= Loc::getMessage('CUSTOM_SETTINGS_COL_VALUE') ?></th>
            <th width="20%"><?= Loc::getMessage('CUSTOM_SETTINGS_COL_ACTIONS') ?></th>
        </tr>
        </thead>
        <tbody>
        <?php foreach ($options as $name => $data): ?>
            <?php
            $type = $data['type'] ?? 'textarea';
            $displayValue = formatOptionValueForDisplay($type, $data['value']);
            $imageSrc = '';
            if ($type === 'image' && (int)$data['value'] > 0) {
                $imageSrc = (string)CFile::GetPath((int)$data['value']);
            }
            $editPayload = htmlspecialchars(json_encode([
                'name' => $name,
                'value' => $data['value'],
                'desc' => $data['desc'],
                'tab' => $data['tab'],
                'type' => $type,
                'variants' => $data['variants'] ?? '',
                'imageSrc' => $imageSrc,
            ], JSON_UNESCAPED_UNICODE), ENT_QUOTES);
            ?>
            <tr>
                <td><strong><?= htmlspecialchars($name) ?></strong></td>
                <td class="option-type"><?= htmlspecialchars($FIELD_TYPES[$type] ?? $type) ?></td>
                <td class="option-desc"><?= htmlspecialchars($data['desc']) ?></td>
                <td class="option-value">
                    <?php if ($imageSrc !== ''): ?>
                        <img class="option-image" src="<?= htmlspecialchars($imageSrc) ?>" alt="">
                    <?php elseif ($type === 'image'): ?>
                        <?= htmlspecialchars(Loc::getMessage('CUSTOM_SETTINGS_IMAGE_EMPTY')) ?>
                    <?php else: ?>
                        <?= htmlspecialchars($displayValue) ?>
                    <?php endif; ?>
                </td>
                <td>
                    <button type="button" class="btn-action" onclick='editOption(<?= $editPayload ?>)'>
                        <?= Loc::getMessage('CUSTOM_SETTINGS_BTN_EDIT') ?>
                    </button>
                    <form method="POST" style="display:inline" onsubmit="return confirm('<?= Loc::getMessage('CUSTOM_SETTINGS_CONFIRM_DELETE') ?>')">
                        <?= bitrix_sessid_post() ?>
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="option_name" value="<?= htmlspecialchars($name) ?>">
                        <input type="hidden" name="return_tab" value="<?= htmlspecialchars($currentTab) ?>">
                        <button type="submit" class="btn-action"><?= Loc::getMessage('CUSTOM_SETTINGS_BTN_DELETE') ?></button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <?php
}
?>

<script>
    var MSG = {
        valueHint: <?= json_encode(Loc::getMessage('CUSTOM_SETTINGS_OPTION_VALUE_HINT')) ?>,
        checkboxYes: <?= json_encode(Loc::getMessage('CUSTOM_SETTINGS_CHECKBOX_YES')) ?>,
        addTitle: <?= json_encode(Loc::getMessage('CUSTOM_SETTINGS_ADD_OPTION')) ?>,
        editTitle: <?= json_encode(Loc::getMessage('CUSTOM_SETTINGS_EDIT_OPTION')) ?>,
        addTab: <?= json_encode(Loc::getMessage('CUSTOM_SETTINGS_BTN_ADD_TAB')) ?>,
        save: <?= json_encode(Loc::getMessage('CUSTOM_SETTINGS_BTN_SAVE')) ?>,
        imageDelete: <?= json_encode(Loc::getMessage('CUSTOM_SETTINGS_IMAGE_DELETE')) ?>,
        imageNote: <?= json_encode(Loc::getMessage('CUSTOM_SETTINGS_IMAGE_NOTE')) ?>
    };

    var currentImageSrc = '';

    function toggleUsageHelp() {
        var box = document.getElementById('usageHelp');
        var arrow = document.getElementById('usageHelpArrow');
        box.classList.toggle('open');
        arrow.textContent = box.classList.contains('open') ? '▲' : '▼';
    }

    function switchTab(tabId) {
        document.querySelectorAll('.tab-content').forEach(function (el) {
            el.classList.remove('active');
        });
        document.querySelectorAll('.custom-tabs li').forEach(function (el) {
            el.classList.remove('active');
        });

        var tabContent = document.getElementById('tab-' + tabId);
        if (tabContent) {
            tabContent.classList.add('active');
        }

        document.querySelectorAll('.custom-tabs li a').forEach(function (el) {
            if (el.getAttribute('onclick') && el.getAttribute('onclick').indexOf("'" + tabId + "'") !== -1) {
                el.parentElement.classList.add('active');
            }
        });

        var url = new URL(window.location.href);
        url.searchParams.set('active_tab', tabId);
        window.history.replaceState({}, '', url);
    }

    function getCurrentValue() {
        var checkbox = document.getElementById('option_value_checkbox');
        if (checkbox) {
            return checkbox.checked ? 'Y' : 'N';
        }
        var field = document.getElementById('option_value');
        return field ? field.value : '';
    }

    function renderValueControl(type, value, variantsRaw, imageSrc) {
        var wrap = document.getElementById('valueControl');
        var html = '';
        imageSrc = imageSrc || '';

        if (type === 'checkbox') {
            html =
                '<div class="checkbox-value-wrap">' +
                '<input type="hidden" name="option_value" value="N">' +
                '<input type="checkbox" id="option_value_checkbox" name="option_value" value="Y"' +
                (value === 'Y' ? ' checked' : '') + '>' +
                '<label for="option_value_checkbox">' + MSG.checkboxYes + '</label>' +
                '</div>';
        } else if (type === 'select') {
            var variants = String(variantsRaw || '').split(/[\r\n;]+/).map(function (v) {
                return v.trim();
            }).filter(Boolean);
            html = '<select id="option_value" name="option_value">';
            if (!variants.length) {
                html += '<option value="">—</option>';
            }
            variants.forEach(function (variant) {
                html += '<option value="' + escapeHtml(variant) + '"' +
                    (variant === value ? ' selected' : '') + '>' + escapeHtml(variant) + '</option>';
            });
            html += '</select>';
        } else if (type === 'text') {
            html = '<input type="text" id="option_value" name="option_value" value="' + escapeAttr(value) +
                '" placeholder="' + escapeAttr(MSG.valueHint) + '">';
        } else if (type === 'number') {
            html = '<input type="number" id="option_value" name="option_value" value="' + escapeAttr(value) +
                '" placeholder="' + escapeAttr(MSG.valueHint) + '">';
        } else if (type === 'password') {
            html = '<input type="password" id="option_value" name="option_value" value="' + escapeAttr(value) +
                '" placeholder="' + escapeAttr(MSG.valueHint) + '" autocomplete="new-password">';
        } else if (type === 'image') {
            html = '';
            if (imageSrc) {
                html += '<div class="image-preview"><img src="' + escapeAttr(imageSrc) + '" alt=""></div>';
            }
            html += '<input type="file" id="option_image" name="option_image" accept="image/jpeg,image/png,image/gif,image/webp">';
            html += '<input type="hidden" id="option_value" name="option_value" value="' + escapeAttr(value) + '">';
            if (value) {
                html += '<label class="checkbox-value-wrap"><input type="checkbox" name="option_image_delete" value="Y"> ' +
                    escapeHtml(MSG.imageDelete) + '</label>';
            }
            html += '<small>' + escapeHtml(MSG.imageNote) + '</small>';
        } else {
            html = '<textarea id="option_value" name="option_value" placeholder="' + escapeAttr(MSG.valueHint) + '">' +
                escapeHtml(value) + '</textarea>';
        }

        wrap.innerHTML = html;
    }

    function onTypeChange() {
        var type = document.getElementById('option_type').value;
        var variantsRow = document.getElementById('variantsRow');
        variantsRow.style.display = (type === 'select') ? 'block' : 'none';
        if (type !== 'image') {
            currentImageSrc = '';
        }
        renderValueControl(type, getCurrentValue(), document.getElementById('option_variants').value, currentImageSrc);
    }

    function showAddForm() {
        document.getElementById('addForm').classList.add('visible');
        document.getElementById('addFormTitle').textContent = MSG.addTitle;
        document.getElementById('option_name').focus();
        document.getElementById('option_name').removeAttribute('readonly');
        document.getElementById('option_type').removeAttribute('disabled');
    }

    function hideAddForm() {
        document.getElementById('addForm').classList.remove('visible');
        document.getElementById('option_name').value = '';
        document.getElementById('option_desc').value = '';
        document.getElementById('option_tab').value = '';
        document.getElementById('option_type').value = 'textarea';
        document.getElementById('option_variants').value = '';
        document.getElementById('option_type').removeAttribute('disabled');
        currentImageSrc = '';
        onTypeChange();
    }

    function editOption(data) {
        showAddForm();
        document.getElementById('addFormTitle').textContent = MSG.editTitle;
        document.getElementById('option_name').value = data.name || '';
        document.getElementById('option_name').setAttribute('readonly', 'readonly');
        document.getElementById('option_desc').value = data.desc || '';
        document.getElementById('option_tab').value = data.tab || '';
        document.getElementById('option_type').value = data.type || 'textarea';
        document.getElementById('option_variants').value = data.variants || '';
        currentImageSrc = data.imageSrc || '';
        onTypeChange();
        renderValueControl(data.type || 'textarea', data.value || '', data.variants || '', currentImageSrc);
    }

    function resetTabForm() {
        document.getElementById('is_new_tab').value = 'Y';
        document.getElementById('edit_tab_id').value = '';
        document.getElementById('tab_name').value = '';
        document.getElementById('tab_sort').value = '500';
        document.getElementById('btnAddTab').value = MSG.addTab;
        document.getElementById('btnCancelTab').style.display = 'none';
    }

    function editTab(id, name, sort) {
        document.getElementById('is_new_tab').value = 'N';
        document.getElementById('edit_tab_id').value = id;
        document.getElementById('tab_name').value = name;
        document.getElementById('tab_sort').value = sort;
        document.getElementById('btnAddTab').value = MSG.save;
        document.getElementById('btnCancelTab').style.display = 'inline-block';
        document.getElementById('tab_name').focus();
    }

    function escapeHtml(str) {
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function escapeAttr(str) {
        return escapeHtml(str).replace(/\n/g, '&#10;');
    }

    document.getElementById('option_variants').addEventListener('change', function () {
        if (document.getElementById('option_type').value === 'select') {
            renderValueControl('select', getCurrentValue(), this.value);
        }
    });

    onTypeChange();
</script>

<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/epilog_admin.php';

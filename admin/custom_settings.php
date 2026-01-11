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
$TABS_KEY = '__tabs';

$RIGHT = $APPLICATION->GetGroupRight($module_id);
if ($RIGHT < 'R') {
    $APPLICATION->AuthForm(Loc::getMessage('ACCESS_DENIED'));
}

// Получение вкладок
function getTabs($module_id, $TABS_KEY) {
    $tabsJson = Option::get($module_id, $TABS_KEY, '[]');
    $tabs = json_decode($tabsJson, true);
    if (!is_array($tabs)) {
        $tabs = [];
    }
    usort($tabs, function($a, $b) {
        return ($a['sort'] ?? 500) - ($b['sort'] ?? 500);
    });
    return $tabs;
}

// Сохранение вкладок
function saveTabs($module_id, $TABS_KEY, $tabs) {
    Option::set($module_id, $TABS_KEY, json_encode($tabs, JSON_UNESCAPED_UNICODE));
}

// Обработка действий
$action = $_REQUEST['action'] ?? '';
$activeTab = $_REQUEST['active_tab'] ?? '';

if ($REQUEST_METHOD === 'POST' && check_bitrix_sessid() && $RIGHT >= 'W') {
    switch ($action) {
        case 'save':
            $optionName = trim($_POST['option_name'] ?? '');
            $optionValue = $_POST['option_value'] ?? '';
            $optionDesc = trim($_POST['option_desc'] ?? '');
            $optionTab = trim($_POST['option_tab'] ?? '');

            if (!empty($optionName) && strpos($optionName, '__') === false) {
                Option::set($module_id, $optionName, $optionValue);
                Option::set($module_id, $optionName . $DESC_SUFFIX, $optionDesc);
                Option::set($module_id, $optionName . $TAB_SUFFIX, $optionTab);
                LocalRedirect($APPLICATION->GetCurPage() . '?saved=Y&active_tab=' . urlencode($optionTab));
            }
            break;

        case 'delete':
            $optionName = $_POST['option_name'] ?? '';
            $returnTab = $_POST['return_tab'] ?? '';
            if (!empty($optionName)) {
                Option::delete($module_id, ['name' => $optionName]);
                Option::delete($module_id, ['name' => $optionName . $DESC_SUFFIX]);
                Option::delete($module_id, ['name' => $optionName . $TAB_SUFFIX]);
                LocalRedirect($APPLICATION->GetCurPage() . '?deleted=Y&active_tab=' . urlencode($returnTab));
            }
            break;

        case 'save_tab':
            $tabId = trim($_POST['tab_id'] ?? '');
            $tabName = trim($_POST['tab_name'] ?? '');
            $tabSort = (int)($_POST['tab_sort'] ?? 500);
            $isNew = $_POST['is_new_tab'] === 'Y';

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
                $tabs = array_filter($tabs, function($tab) use ($tabId) {
                    return $tab['id'] !== $tabId;
                });
                saveTabs($module_id, $TABS_KEY, array_values($tabs));
                
                // Сбрасываем привязку настроек к удалённой вкладке
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

// Контекстное меню
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

// Уведомления
if ($_GET['saved'] === 'Y') {
    CAdminMessage::ShowNote(Loc::getMessage('CUSTOM_SETTINGS_SAVED'));
}
if ($_GET['deleted'] === 'Y') {
    CAdminMessage::ShowNote(Loc::getMessage('CUSTOM_SETTINGS_DELETED'));
}
if ($_GET['tab_saved'] === 'Y') {
    CAdminMessage::ShowNote(Loc::getMessage('CUSTOM_SETTINGS_TAB_SAVED'));
}
if ($_GET['tab_deleted'] === 'Y') {
    CAdminMessage::ShowNote(Loc::getMessage('CUSTOM_SETTINGS_TAB_DELETED'));
}

// Получаем вкладки
$tabs = getTabs($module_id, $TABS_KEY);

// Получаем все настройки модуля
$allOptionsRaw = Option::getForModule($module_id);
$allOptions = [];

foreach ($allOptionsRaw as $name => $value) {
    if (strpos($name, '__') !== false) {
        continue;
    }
    
    $descKey = $name . $DESC_SUFFIX;
    $tabKey = $name . $TAB_SUFFIX;
    $allOptions[$name] = [
        'value' => $value,
        'desc' => $allOptionsRaw[$descKey] ?? '',
        'tab' => $allOptionsRaw[$tabKey] ?? '',
    ];
}

// Группируем настройки по вкладкам
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

// Определяем активную вкладку
if (empty($activeTab)) {
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
    .form-row input[type="text"], .form-row textarea, .form-row select { width: 100%; max-width: 500px; border: 1px solid #c8d3d5; border-radius: 3px; box-sizing: border-box; }
    .form-row textarea { min-height: 80px; }
    .form-row small { color: #666; display: block; margin-top: 4px; }
    .form-row-inline { display: flex; gap: 10px; align-items: flex-end; flex-wrap: wrap; }
    .form-row-inline .form-row { margin-bottom: 0; flex: 1; min-width: 200px; }
    .form-row-inline .form-row.narrow { flex: 0 0 100px; min-width: 100px; }
    .form-row-inline .form-row.btn-row { flex: 0 0 auto; min-width: auto; }
    
    .btn-action { padding: 5px 10px; margin-right: 5px; cursor: pointer; }
    .option-value { max-width: 350px; word-break: break-word; }
    .option-desc { color: #666; font-size: 12px; max-width: 200px; }
    
    .tabs-list { margin-top: 20px; }
    .tabs-list table { width: 100%; border-collapse: collapse; }
    .tabs-list th, .tabs-list td { border: 1px solid #e0e8ea; padding: 8px; text-align: left; }
    .tabs-list th { background: #f5f9f9; }
    
    .no-options { color: #999; padding: 20px; text-align: center; }
    .tab-add-form { background: #fff; padding: 15px; border: 1px solid #e0e8ea; border-radius: 4px; margin-bottom: 20px; }
</style>

<!-- Форма добавления настройки -->
<div id="addForm" class="add-form">
    <h3><?= Loc::getMessage('CUSTOM_SETTINGS_ADD_OPTION') ?></h3>
    <form method="POST" action="<?= $APPLICATION->GetCurPage() ?>">
        <?= bitrix_sessid_post() ?>
        <input type="hidden" name="action" value="save">

        <div class="form-row">
            <label for="option_name"><?= Loc::getMessage('CUSTOM_SETTINGS_OPTION_NAME') ?>:</label>
            <input type="text" id="option_name" name="option_name" required
                   placeholder="<?= Loc::getMessage('CUSTOM_SETTINGS_OPTION_NAME_HINT') ?>">
            <small><?= Loc::getMessage('CUSTOM_SETTINGS_OPTION_NAME_NOTE') ?></small>
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

        <div class="form-row">
            <label for="option_value"><?= Loc::getMessage('CUSTOM_SETTINGS_OPTION_VALUE') ?>:</label>
            <textarea id="option_value" name="option_value"
                      placeholder="<?= Loc::getMessage('CUSTOM_SETTINGS_OPTION_VALUE_HINT') ?>"></textarea>
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

<!-- Содержимое вкладок -->
<?php foreach ($tabs as $tab): ?>
    <div id="tab-<?= htmlspecialchars($tab['id']) ?>" class="tab-content <?= $activeTab === $tab['id'] ? 'active' : '' ?>">
        <?php if (!empty($optionsByTab[$tab['id']])): ?>
            <?php renderOptionsTable($optionsByTab[$tab['id']], $tabs, $tab['id']); ?>
        <?php else: ?>
            <div class="no-options"><?= Loc::getMessage('CUSTOM_SETTINGS_TAB_EMPTY') ?></div>
        <?php endif; ?>
    </div>
<?php endforeach; ?>

<!-- Без категории -->
<div id="tab-__no_tab" class="tab-content <?= $activeTab === '__no_tab' ? 'active' : '' ?>">
    <?php if (!empty($optionsByTab['__no_tab'])): ?>
        <?php renderOptionsTable($optionsByTab['__no_tab'], $tabs, '__no_tab'); ?>
    <?php else: ?>
        <div class="no-options"><?= Loc::getMessage('CUSTOM_SETTINGS_TAB_EMPTY') ?></div>
    <?php endif; ?>
</div>

<!-- Управление вкладками -->
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
function renderOptionsTable($options, $tabs, $currentTab) {
    ?>
    <table class="settings-table">
        <thead>
        <tr>
            <th width="20%"><?= Loc::getMessage('CUSTOM_SETTINGS_COL_NAME') ?></th>
            <th width="20%"><?= Loc::getMessage('CUSTOM_SETTINGS_COL_DESC') ?></th>
            <th width="40%"><?= Loc::getMessage('CUSTOM_SETTINGS_COL_VALUE') ?></th>
            <th width="20%"><?= Loc::getMessage('CUSTOM_SETTINGS_COL_ACTIONS') ?></th>
        </tr>
        </thead>
        <tbody>
        <?php foreach ($options as $name => $data): ?>
            <tr>
                <td><strong><?= htmlspecialchars($name) ?></strong></td>
                <td class="option-desc"><?= htmlspecialchars($data['desc']) ?></td>
                <td class="option-value"><?= htmlspecialchars($data['value']) ?></td>
                <td>
                    <button type="button" class="btn-action" onclick="editOption('<?= htmlspecialchars($name, ENT_QUOTES) ?>', '<?= htmlspecialchars($data['value'], ENT_QUOTES) ?>', '<?= htmlspecialchars($data['desc'], ENT_QUOTES) ?>', '<?= htmlspecialchars($data['tab'], ENT_QUOTES) ?>')">
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
    function switchTab(tabId) {
        // Скрыть все вкладки
        document.querySelectorAll('.tab-content').forEach(function(el) {
            el.classList.remove('active');
        });
        document.querySelectorAll('.custom-tabs li').forEach(function(el) {
            el.classList.remove('active');
        });
        
        // Показать выбранную
        var tabContent = document.getElementById('tab-' + tabId);
        if (tabContent) {
            tabContent.classList.add('active');
        }
        
        // Активировать пункт меню
        document.querySelectorAll('.custom-tabs li a').forEach(function(el) {
            if (el.getAttribute('onclick').indexOf("'" + tabId + "'") !== -1) {
                el.parentElement.classList.add('active');
            }
        });
        
        // Сохранить в URL без перезагрузки
        var url = new URL(window.location.href);
        url.searchParams.set('active_tab', tabId);
        window.history.replaceState({}, '', url);
    }

    function showAddForm() {
        document.getElementById('addForm').classList.add('visible');
        document.getElementById('option_name').focus();
        document.getElementById('option_name').removeAttribute('readonly');
    }

    function hideAddForm() {
        document.getElementById('addForm').classList.remove('visible');
        document.getElementById('option_name').value = '';
        document.getElementById('option_desc').value = '';
        document.getElementById('option_value').value = '';
        document.getElementById('option_tab').value = '';
    }

    function editOption(name, value, desc, tab) {
        showAddForm();
        document.getElementById('option_name').value = name;
        document.getElementById('option_name').setAttribute('readonly', 'readonly');
        document.getElementById('option_desc').value = desc;
        document.getElementById('option_value').value = value;
        document.getElementById('option_tab').value = tab;
    }

    function resetTabForm() {
        document.getElementById('is_new_tab').value = 'Y';
        document.getElementById('edit_tab_id').value = '';
        document.getElementById('tab_name').value = '';
        document.getElementById('tab_sort').value = '500';
        document.getElementById('btnAddTab').value = '<?= Loc::getMessage('CUSTOM_SETTINGS_BTN_ADD_TAB') ?>';
        document.getElementById('btnCancelTab').style.display = 'none';
    }

    function editTab(id, name, sort) {
        document.getElementById('is_new_tab').value = 'N';
        document.getElementById('edit_tab_id').value = id;
        document.getElementById('tab_name').value = name;
        document.getElementById('tab_sort').value = sort;
        document.getElementById('btnAddTab').value = '<?= Loc::getMessage('CUSTOM_SETTINGS_BTN_SAVE') ?>';
        document.getElementById('btnCancelTab').style.display = 'inline-block';
        document.getElementById('tab_name').focus();
    }
</script>

<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/epilog_admin.php';

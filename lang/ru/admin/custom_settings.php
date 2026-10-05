<?php
$MESS['ACCESS_DENIED'] = 'Доступ запрещён';

$MESS['CUSTOM_SETTINGS_PAGE_TITLE'] = 'Управление настройками';

// Контекстное меню
$MESS['CUSTOM_SETTINGS_ADD_NEW'] = 'Добавить настройку';
$MESS['CUSTOM_SETTINGS_MANAGE_TABS'] = 'Вкладки';
$MESS['CUSTOM_SETTINGS_MODULE_OPTIONS'] = 'Настройки модуля';

// Сообщения
$MESS['CUSTOM_SETTINGS_SAVED'] = 'Настройка сохранена';
$MESS['CUSTOM_SETTINGS_DELETED'] = 'Настройка удалена';
$MESS['CUSTOM_SETTINGS_TAB_SAVED'] = 'Вкладка сохранена';
$MESS['CUSTOM_SETTINGS_TAB_DELETED'] = 'Вкладка удалена';

// Форма настройки
$MESS['CUSTOM_SETTINGS_ADD_OPTION'] = 'Добавить настройку';
$MESS['CUSTOM_SETTINGS_EDIT_OPTION'] = 'Изменить настройку';
$MESS['CUSTOM_SETTINGS_OPTION_NAME'] = 'Код настройки';
$MESS['CUSTOM_SETTINGS_OPTION_NAME_HINT'] = 'Например: site_phone, api_key';
$MESS['CUSTOM_SETTINGS_OPTION_NAME_NOTE'] = 'Уникальный идентификатор (латиница, без пробелов)';
$MESS['CUSTOM_SETTINGS_OPTION_TYPE'] = 'Тип поля';
$MESS['CUSTOM_SETTINGS_OPTION_TAB'] = 'Вкладка';
$MESS['CUSTOM_SETTINGS_OPTION_DESC'] = 'Описание';
$MESS['CUSTOM_SETTINGS_OPTION_DESC_HINT'] = 'Краткое описание настройки';
$MESS['CUSTOM_SETTINGS_OPTION_VALUE'] = 'Значение';
$MESS['CUSTOM_SETTINGS_OPTION_VALUE_HINT'] = 'Введите значение настройки';
$MESS['CUSTOM_SETTINGS_OPTION_VARIANTS'] = 'Варианты списка';
$MESS['CUSTOM_SETTINGS_OPTION_VARIANTS_HINT'] = 'Каждый вариант с новой строки';
$MESS['CUSTOM_SETTINGS_OPTION_VARIANTS_NOTE'] = 'Можно разделять переносом строки или точкой с запятой';
$MESS['CUSTOM_SETTINGS_NO_TAB'] = '-- Без вкладки --';

// Типы полей
$MESS['CUSTOM_SETTINGS_TYPE_TEXT'] = 'Строка (text)';
$MESS['CUSTOM_SETTINGS_TYPE_TEXTAREA'] = 'Текст (textarea)';
$MESS['CUSTOM_SETTINGS_TYPE_CHECKBOX'] = 'Флажок (checkbox)';
$MESS['CUSTOM_SETTINGS_TYPE_NUMBER'] = 'Число (number)';
$MESS['CUSTOM_SETTINGS_TYPE_PASSWORD'] = 'Пароль (password)';
$MESS['CUSTOM_SETTINGS_TYPE_SELECT'] = 'Список (select)';
$MESS['CUSTOM_SETTINGS_TYPE_IMAGE'] = 'Изображение (image)';
$MESS['CUSTOM_SETTINGS_CHECKBOX_YES'] = 'Да';
$MESS['CUSTOM_SETTINGS_CHECKBOX_NO'] = 'Нет';
$MESS['CUSTOM_SETTINGS_IMAGE_DELETE'] = 'Удалить изображение';
$MESS['CUSTOM_SETTINGS_IMAGE_NOTE'] = 'JPG, PNG, GIF или WEBP. При редактировании новый файл заменяет текущий.';
$MESS['CUSTOM_SETTINGS_IMAGE_SAVE_ERROR'] = 'Не удалось сохранить изображение';
$MESS['CUSTOM_SETTINGS_IMAGE_EMPTY'] = 'Файл не загружен';

// Вкладки
$MESS['CUSTOM_SETTINGS_NO_TAB_TITLE'] = 'Общие';
$MESS['CUSTOM_SETTINGS_ADD_TAB_TITLE'] = 'Добавить вкладку';
$MESS['CUSTOM_SETTINGS_TAB_NAME'] = 'Название';
$MESS['CUSTOM_SETTINGS_TAB_NAME_HINT'] = 'Название вкладки';
$MESS['CUSTOM_SETTINGS_TAB_SORT'] = 'Сорт.';
$MESS['CUSTOM_SETTINGS_TAB_COUNT'] = 'Настроек';
$MESS['CUSTOM_SETTINGS_TAB_EMPTY'] = 'В этой вкладке пока нет настроек';
$MESS['CUSTOM_SETTINGS_NO_TABS'] = 'Вкладки не созданы';

// Таблица
$MESS['CUSTOM_SETTINGS_COL_NAME'] = 'Код';
$MESS['CUSTOM_SETTINGS_COL_TYPE'] = 'Тип';
$MESS['CUSTOM_SETTINGS_COL_DESC'] = 'Описание';
$MESS['CUSTOM_SETTINGS_COL_VALUE'] = 'Значение';
$MESS['CUSTOM_SETTINGS_COL_ACTIONS'] = 'Действия';

// Кнопки
$MESS['CUSTOM_SETTINGS_BTN_SAVE'] = 'Сохранить';
$MESS['CUSTOM_SETTINGS_BTN_CANCEL'] = 'Отмена';
$MESS['CUSTOM_SETTINGS_BTN_EDIT'] = 'Изменить';
$MESS['CUSTOM_SETTINGS_BTN_DELETE'] = 'Удалить';
$MESS['CUSTOM_SETTINGS_BTN_ADD_TAB'] = 'Добавить';

// Подтверждения
$MESS['CUSTOM_SETTINGS_CONFIRM_DELETE'] = 'Удалить эту настройку?';
$MESS['CUSTOM_SETTINGS_CONFIRM_DELETE_TAB'] = 'Удалить вкладку? Настройки будут перемещены в "Общие".';

// Пустой список
$MESS['CUSTOM_SETTINGS_NO_OPTIONS'] = 'Настройки не найдены. Нажмите "Добавить настройку" для создания.';

// Справка по выводу
$MESS['CUSTOM_SETTINGS_USAGE_TITLE'] = 'Как вывести значение настройки на странице';
$MESS['CUSTOM_SETTINGS_USAGE_INTRO'] = 'Значения хранятся через \\Bitrix\\Main\\Config\\Option в модуле <code>custom.settings</code>. Код настройки — это идентификатор, который вы задаёте при создании (например, <code>site_phone</code>).';
$MESS['CUSTOM_SETTINGS_USAGE_BASIC_TITLE'] = 'Базовый пример';
$MESS['CUSTOM_SETTINGS_USAGE_BASIC_CODE'] = <<<'CODE'
use Bitrix\Main\Config\Option;

$moduleId = 'custom.settings';

// Получить значение (третий параметр — значение по умолчанию)
$phone = Option::get($moduleId, 'site_phone', '');

echo htmlspecialcharsbx($phone);
CODE;
$MESS['CUSTOM_SETTINGS_USAGE_TYPES_TITLE'] = 'Особенности по типам полей';
$MESS['CUSTOM_SETTINGS_USAGE_TYPE_TEXT'] = '<code>text</code> / <code>textarea</code> / <code>password</code> / <code>select</code> — строка';
$MESS['CUSTOM_SETTINGS_USAGE_TYPE_CHECKBOX'] = '<code>checkbox</code> — строка <code>Y</code> или <code>N</code>';
$MESS['CUSTOM_SETTINGS_USAGE_TYPE_NUMBER'] = '<code>number</code> — число в виде строки, при необходимости приведите к <code>(int)</code> / <code>(float)</code>';
$MESS['CUSTOM_SETTINGS_USAGE_TYPE_SELECT'] = 'для <code>select</code> в Option хранится выбранный вариант';
$MESS['CUSTOM_SETTINGS_USAGE_TYPE_IMAGE'] = '<code>image</code> — в Option хранится ID файла. Путь к картинке: <code>CFile::GetPath($fileId)</code>';
$MESS['CUSTOM_SETTINGS_USAGE_TYPES_CODE'] = <<<'CODE'
$enabled = Option::get($moduleId, 'feature_enabled', 'N') === 'Y';
$limit = (int)Option::get($moduleId, 'items_limit', '10');
$mode = Option::get($moduleId, 'display_mode', 'list');

$fileId = (int)Option::get($moduleId, 'site_logo', '0');
$logo = $fileId > 0 ? CFile::GetPath($fileId) : '';

if ($enabled) {
    // ...
}
CODE;
$MESS['CUSTOM_SETTINGS_USAGE_TEMPLATE_TITLE'] = 'В шаблоне компонента / header.php';
$MESS['CUSTOM_SETTINGS_USAGE_TEMPLATE_NOTE'] = 'Подключите класс Option и выведите значение с экранированием:';
$MESS['CUSTOM_SETTINGS_USAGE_TEMPLATE_CODE'] = <<<'CODE'
<?php
use Bitrix\Main\Config\Option;
$email = Option::get('custom.settings', 'support_email', '');
?>
<a href="mailto:<?= htmlspecialcharsbx($email) ?>"><?= htmlspecialcharsbx($email) ?></a>
CODE;
$MESS['CUSTOM_SETTINGS_USAGE_ALL_TITLE'] = 'Получить все настройки модуля';
$MESS['CUSTOM_SETTINGS_USAGE_ALL_CODE'] = <<<'CODE'
$all = Option::getForModule('custom.settings');
// Служебные ключи с суффиксами __desc, __tab, __type, __variants — метаданные
CODE;
$MESS['CUSTOM_SETTINGS_USAGE_NOTE'] = 'Важно: при выводе в HTML всегда экранируйте значения (<code>htmlspecialcharsbx</code>), кроме случаев, когда вы намеренно выводите доверенный HTML.';

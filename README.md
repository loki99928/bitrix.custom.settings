# Модуль глобальных настроек для 1С-Битрикс

Модуль `custom.settings` предоставляет административный интерфейс для создания и управления произвольными настройками сайта через `\Bitrix\Main\Config\Option`.

## Установка

1. Модуль должен лежать в `/local/modules/custom.settings/`
2. Административная панель → Настройки → Модули → Установить
3. При установке в `/bitrix/admin/` копируется `custom_settings.php`

Страница: `/bitrix/admin/custom_settings.php?lang=ru`

## Административная часть

**Настройки → Глобальные настройки → Все настройки**

- Добавление новых настроек
- Выбор типа поля: text, textarea, checkbox, number, password, select
- Редактирование существующих
- Удаление настроек

Все поля создаются пользователем через интерфейс.

Тип хранится в `Option` с суффиксом `__type`, варианты списка — в `__variants`.

## Использование в коде

```php
use Bitrix\Main\Config\Option;

$module_id = 'custom.settings';

// Получить настройку
$value = Option::get($module_id, 'my_setting', 'default');

// Все настройки модуля
$all = Option::getForModule($module_id);
```

## Требования

- 1С-Битрикс 14.00.00+
- PHP 7.4+

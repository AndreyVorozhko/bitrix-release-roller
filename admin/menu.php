<?php
use Bitrix\Main\Loader;

if (!Loader::includeModule('vorozhko.roller')) {
    return;
}

return [
    'parent_menu' => 'global_menu_content',
    'sort' => 70,
    'icon' => 'sys_menu_icon',
    'page_icon' => 'sys_page_icon',
    'items_id' => 'vorozhko.roller',
    'text' => 'Раскатчик релизов',
    'items' => [
        [
            'text' => 'Раскатка и бекап',
            'url' => '/bitrix/admin/roller.php',
        ],
        [
            'text' => 'Актуализатор',
            'url' => '/bitrix/admin/actualizator.php',
        ],
    ],
];
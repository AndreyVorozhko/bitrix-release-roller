<?php

try {
    Bitrix\Main\Loader::registerAutoloadClasses(
        'vorozhko.roller',
        [
            "ReleaseRoller" => "classes/general/ReleaseRoller.php",
            "HlBlockHelper" => "classes/general/HlBlockHelper.php",
        ]
    );
} catch (\Bitrix\Main\LoaderException $e) {
    echo "Ошибка автозагрузки классов: {$e->getMessage()}";
}

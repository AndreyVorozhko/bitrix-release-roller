<?php

// защитим скрипт от перегруза
set_time_limit(3);

use Bitrix\Main\Application;
use Bitrix\Main\Loader;

require_once($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/prolog_admin_before.php");

if(CMain::getGroupRight('vorozhko.roller') < 'W') {
    echo('Доступ запрещен');
}

// Если модуль загружен, то доступен неймспейс Vorozhko\Roller
// т.е. из папки lib можно грузить классы автоматом
if (!Loader::includeModule('vorozhko.roller')) {
    return;
}

$APPLICATION->SetTitle('Актуализатор');

$request = Application::getInstance()->getContext()->getRequest();

require_once "{$_SERVER['DOCUMENT_ROOT']}/bitrix/modules/main/include/prolog_admin_after.php";

if (empty($request->getPostList()->getValues())):?>
    <form method="POST" action="<?echo $APPLICATION->GetCurPage()?>">
        <input type="hidden" name="logical" value="<?=htmlspecialcharsbx($logical)?>">
        <?echo GetFilterHiddens("filter_");?>
        <input type="hidden" name="save" value="Y">
        <?=bitrix_sessid_post()?>

        <?
        $aTabs = array(
            array("DIV" => "edit1", "TAB" => "Сборка актуализации", "ICON" => "fileman", "TITLE" => 'Вставьте список маршрутов'),
        );
        $tabControl = new CAdminTabControl("tabControl", $aTabs, true, true);
        $tabControl->Begin();
        $tabControl->BeginNextTab();
        ?>

        <tr>
            <td><textarea name="routes" id="" cols="60" rows="25" placeholder="Список маршрутов"></textarea></td>
        </tr>
        <?$tabControl->EndTab();
        $tabControl->Buttons(
            array(
                "disabled" => false,
                "btnSave" => false,
                "back_url" => false,
            )
        );
        $tabControl->End();
        ?>
    </form>
<?php
else:

    try {
        (new \Vorozhko\Roller\Actualizator())->build();
    } catch (\Exception $e) {
        echo (new CAdminMessage([
            'TYPE' => 'ERROR',
            'MESSAGE' => 'Ошибка сборки актуализации',
            'DETAILS' => $e->getMessage(),
        ]))->Show();
    }
    ?>
    <div class="btn_submit"><a href="/bitrix/admin/actualizator.php" class="adm-btn-save" style="font-weight:bold;text-decoration:none;">Назад</a></div>
<?php
endif;

require_once("{$_SERVER['DOCUMENT_ROOT']}/bitrix/modules/main/include/epilog_admin.php");

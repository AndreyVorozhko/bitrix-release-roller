<?php

use Bitrix\Main\Application;
use Bitrix\Main\Loader;

use Bitrix\Main\Localization\Loc;

require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_before.php");

if(CMain::getGroupRight('vorozhko.roller') < 'W') {
    echo('Доступ запрещен');
}

if (!Loader::includeModule('vorozhko.roller')) {
    return;
}

$APPLICATION->SetTitle('Раскатка и бекап релизов');

$request = Application::getInstance()->getContext()->getRequest();

$hlHelper = new HlBlockHelper();

if (! $hlHelper->isExists()) {
    require_once "{$_SERVER['DOCUMENT_ROOT']}/bitrix/modules/main/include/prolog_admin_after.php";
    CAdminMessage::ShowMessage([
        'MESSAGE' => Loc::getMessage('HLBLOCK_RELEASES_NOT_FOUND'),
        'type' => 'ERROR',
    ]);
    require_once("{$_SERVER['DOCUMENT_ROOT']}/bitrix/modules/main/include/epilog_admin.php");
    die;
}

require_once "{$_SERVER['DOCUMENT_ROOT']}/bitrix/modules/main/include/prolog_admin_after.php";
?>
<?php

// Тут вытащим 10 последних релизов
$hlDataClass = $hlHelper->getDataClass();

$lastReleases = $hlDataClass::query()
    ->setSelect(['*', 'USER_NAME' => 'USER_RELISER.NAME', 'USER_LAST_NAME' => 'USER_RELISER.LAST_NAME'])
    ->setOrder(['ID' => 'DESC'])
    ->setLimit(10)
    ->registerRuntimeField('USER_RELISER', [
        'data_type' => \Bitrix\Main\UserTable::class,
        'reference' => [
            // Притягиваем по ID юзера
            '=this.UF_RELEASE_USER_ID' => 'ref.ID',
        ],
    ])
    ->setCacheTtl(3600)
    ->cacheJoins(true)
    ->exec()
    ->fetchAll()
;

$lang = Bitrix\Main\Application::getInstance()->getContext()->getLanguage();

if (empty($request->getPostList()->getValues())):?>
    <script>
        function NewFileName(ob)
        {
            var
                str_filename,
                filename,
                str_file = ob.value,
                num = ob.name;

            num  = num.substr(num.lastIndexOf("_")+1);
            str_file = str_file.replace(/\\/g, '/');
            filename = str_file.substr(str_file.lastIndexOf("/")+1);
            document.ffilemanupload["filename_"+num].value = filename;
            if(document.ffilemanupload.nums.value==num)
            {
                num++;
                var tbl = BX("bx-upload-tbl");
                var cnt = tbl.rows.length;
                var oRow = tbl.insertRow(cnt);
                var oCell = oRow.insertCell(0);
                oCell.className = "adm-detail-content-cell-l";
                oCell.innerHTML = '<input type="text" name="filename_'+num+'" size="30" maxlength="255" value="">';
                var oCell = oRow.insertCell(1);
                oCell.className = "adm-detail-content-cell-r";
                oCell.innerHTML = '<input type="file" name="file_'+num+'" size="30" maxlength="255" value="" onChange="NewFileName(this)">';

                document.ffilemanupload.nums.value = num;
            }

            BX.adminPanel.modifyFormElements(BX("bx-upload-tbl"));
        }
    </script>
    <form method="POST" action="<?echo $APPLICATION->GetCurPage()?>" name="ffilemanupload" enctype="multipart/form-data">
        <input type="hidden" name="logical" value="<?=htmlspecialcharsbx($logical)?>">
        <?echo GetFilterHiddens("filter_");?>
        <input type="hidden" name="save" value="Y">
        <?=bitrix_sessid_post()?>

        <?
        $aTabs = array(
            array("DIV" => "edit1", "TAB" => "Раскатка релиза", "ICON" => "fileman", "TITLE" => ''),
        );
        $tabControl = new CAdminTabControl("tabControl", $aTabs, true, true);
        $tabControl->Begin();
        $tabControl->BeginNextTab();
        ?>

        <tr>
            <td><input type="text" name="release_num" placeholder="Номер релиза*"></td>
        </tr>
        <tr>
            <td><textarea name="description" id="" cols="50" rows="10" placeholder="Описание"></textarea></td>
        </tr>
        <tr>
            <td>Релиз: <input type="radio" name="is_backup" value="0" checked></td>
        </tr>
        <tr>
            <td>Бекап: <input type="radio" name="is_backup" value="1"> (бекап при деплое не создается)</td>
        </tr>
        <tr>
            <td colspan="2" align="left">
                <input type="hidden" name="nums" value="5">
                <table id="bx-upload-tbl">
                    <tr class="heading">
                        <td>
                            Файл Релиза
                        </td class="adm-detail-content-cell-r">
                    </tr>
                        <tr>
                            <td class="adm-detail-content-cell-r">
                                <input type="file" name="file_<?echo $i?>" size="30" maxlength="255" value="" onChange="NewFileName(this)">
                            </td>
                        </tr>
                </table>
            </td>
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
    <!-- Так же хочется видеть маленькую табличку с 10 последними релизами -->

<table style="width:100%;margin-bottom: 10px;">
  <tr>
    <th>ID</th>
    <th>Номер релиза</th>
    <th>Дата и время</th>
    <th>Релизер</th>
    <th>Бекап?</th>
    <th>Описание</th>
  </tr>

    <?php
    foreach ($lastReleases as $release): ?>
        <tr>
            <td><?=$release['ID']?></td>
            <td><?=$release['UF_RELEASE_NUM']?></td>
            <td><?=$release['UF_RELEASE_TIME']->format('Y-m-d H:i:s')?></td>
            <td><a href="/bitrix/admin/user_edit.php?lang=<?=$lang?>&ID=<?=$release['UF_RELEASE_USER_ID']?>"><?=$release['USER_NAME'] . ' ' . $release['USER_LAST_NAME']?></a></td>
            <td><?= $release['UF_RELEASE_IS_BACKUP'] ? 'да' : 'нет' ?></td>
            <td><?=$release['UF_RELEASE_DESCRIPTION']?></td>
        </tr>
    <?php endforeach; ?>
</table>
    <a
        href="/bitrix/admin/highloadblock_rows_list.php?ENTITY_ID=<?=$hlHelper->getEntityId()?>&lang=<?=$lang?>"
        class="adm-btn-save" style="font-weight:bold;text-decoration:none;"
    >Подробнее</a>
<?php
else:

    try {
        (new \ReleaseRoller($request, $hlHelper))->roll();
    } catch (\Exception $e) {
        echo (new CAdminMessage([
            'TYPE' => 'ERROR',
            'MESSAGE' => 'Ошибка раскатки релиза',
            'DETAILS' => $e->getMessage(),
        ]))->Show();
    }
    ?>
    <div class="btn_submit"><a href="/bitrix/admin/roller.php" class="adm-btn-save" style="font-weight:bold;text-decoration:none;">Назад</a></div>
<?php
endif;

require_once("{$_SERVER['DOCUMENT_ROOT']}/bitrix/modules/main/include/epilog_admin.php");
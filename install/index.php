<?php


use Bitrix\Main\Application;
use Bitrix\Main\ModuleManager;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Highloadblock as HL;

Loc::loadLanguageFile(__FILE__);
try {
    Loader::includeModule('highloadblock');
} catch (\Bitrix\Main\LoaderException $e) {
    throw new RuntimeException('Не установлен модуль hl-блоков.');
}

class vorozhko_roller extends CModule
{
    public    $MODULE_ID   = 'vorozhko.roller';
    protected $installPath = '';

    public function __construct()
    {
        $arModuleVersion = [];

        include(__DIR__ . '/version.php');

        if (is_array($arModuleVersion) && array_key_exists('VERSION', $arModuleVersion))
        {
            $this->MODULE_VERSION      = $arModuleVersion['VERSION'];
            $this->MODULE_VERSION_DATE = $arModuleVersion['VERSION_DATE'];
            $this->PARTNER_NAME = Loc::getMessage("SPER_PARTNER");
            $this->PARTNER_URI = Loc::getMessage("PARTNER_URI");
        }

        $this->MODULE_NAME        = Loc::getMessage("RELEASER_NAME");
        $this->MODULE_DESCRIPTION = Loc::getMessage("RELEASER_DESCRIPTION");
    }

    public function DoInstall(): void
    {
        $this->InstallFiles();
        $this->InstallDB();

        ModuleManager::registerModule($this->MODULE_ID);
        Loader::includeModule($this->MODULE_ID);
    }

    public function DoUninstall(): void
    {
        global $USER;

        if (!$USER->IsAdmin()) {
            return;
        }

        Loader::includeModule($this->MODULE_ID);

        ModuleManager::unRegisterModule($this->MODULE_ID);

        $this->UnInstallFiles();
        $this->UnInstallDB();
    }

    public function InstallFiles($arParams = []): bool
    {
        CopyDirFiles(
            __DIR__ . '/admin/',
            Application::getDocumentRoot().'/bitrix/admin',
            true,
            true
        );

        return true;
    }

    public function UnInstallFiles($arParams = []): bool
    {
        unlink(Application::getDocumentRoot().'/bitrix/admin/roller.php');
        unlink(Application::getDocumentRoot().'/bitrix/admin/actualizator.php');

        return true;
    }

    public function InstallDB($arParams = []): bool
    {
        // Имена для hl-блока
        $arLangNames = [
            'ru' => 'Раскатчик релизов',
            'en' => 'Release roller'
        ];

        // Создаем собственно hl-блок
        $result = HL\HighloadBlockTable::add([
            'NAME' => 'Releases',
            'TABLE_NAME' => 'hl_releases',
        ]);

        // Сохраняем языковые параметры
        if ($result->isSuccess()) {
            $id = $result->getId();
            foreach($arLangNames as $lang_key => $lang_val){
                HL\HighloadBlockLangTable::add([
                    'ID' => $id,
                    'LID' => $lang_key,
                    'NAME' => $lang_val
                ]);
            }
        } else {
            $errors = $result->getErrorMessages();
            var_dump($errors);
            die();
        }

        // Создадим все свойства
        $UFObject = 'HLBLOCK_'.$id;

        $arCartFields = [
            'UF_RELEASE_NUM'=>[
                'ENTITY_ID' => $UFObject,
                'FIELD_NAME' => 'UF_RELEASE_NUM',
                'USER_TYPE_ID' => 'string',
                'SORT' => 100,
                'MANDATORY' => 'N',
                "EDIT_FORM_LABEL" => ['ru'=>'Номер релиза', 'en'=>'Release number'],
                "LIST_COLUMN_LABEL" => ['ru'=>'Номер релиза', 'en'=>'Release number'],
                "LIST_FILTER_LABEL" => ['ru'=>'Номер релиза', 'en'=>'Release number'],
                "ERROR_MESSAGE" => ['ru'=>'', 'en'=>''],
                "HELP_MESSAGE" => ['ru'=>'', 'en'=>''],
            ],
            'UF_RELEASE_TIME'=>[
                'ENTITY_ID' => $UFObject,
                'FIELD_NAME' => 'UF_RELEASE_TIME',
                'USER_TYPE_ID' => 'datetime',
                'SORT' => 200,
                'MANDATORY' => 'N',
                "EDIT_FORM_LABEL" => ['ru'=>'Дата раскатки', 'en'=>'Rolling date'],
                "LIST_COLUMN_LABEL" => ['ru'=>'Дата раскатки', 'en'=>'Rolling date'],
                "LIST_FILTER_LABEL" => ['ru'=>'Дата раскатки', 'en'=>'Rolling date'],
                "ERROR_MESSAGE" => ['ru'=>'', 'en'=>''],
                "HELP_MESSAGE" => ['ru'=>'', 'en'=>''],
            ],
            'UF_RELEASE_USER_ID'=>[
                'ENTITY_ID' => $UFObject,
                'FIELD_NAME' => 'UF_RELEASE_USER_ID',
                'USER_TYPE_ID' => 'integer',
                'SORT' => 300,
                'MANDATORY' => 'N',
                "EDIT_FORM_LABEL" => ['ru'=>'Кто релизил', 'en'=>'Who releases'],
                "LIST_COLUMN_LABEL" => ['ru'=>'Кто релизил', 'en'=>'Who releases'],
                "LIST_FILTER_LABEL" => ['ru'=>'Кто релизил', 'en'=>'Who releases'],
                "ERROR_MESSAGE" => ['ru'=>'', 'en'=>''],
                "HELP_MESSAGE" => ['ru'=>'', 'en'=>''],
            ],
            'UF_RELEASE_IS_BACKUP'=>[
                'ENTITY_ID' => $UFObject,
                'FIELD_NAME' => 'UF_RELEASE_IS_BACKUP',
                'USER_TYPE_ID' => 'boolean',
                'SORT' => 400,
                'MANDATORY' => 'N',
                "EDIT_FORM_LABEL" => ['ru'=>'Это бекап?', 'en'=>'Is backup'],
                "LIST_COLUMN_LABEL" => ['ru'=>'Это бекап?', 'en'=>'Is backup'],
                "LIST_FILTER_LABEL" => ['ru'=>'Это бекап?', 'en'=>'Is backup'],
                "ERROR_MESSAGE" => ['ru'=>'', 'en'=>''],
                "HELP_MESSAGE" => ['ru'=>'', 'en'=>''],
            ],
            'UF_RELEASE_DESCRIPTION'=>[
                'ENTITY_ID' => $UFObject,
                'FIELD_NAME' => 'UF_RELEASE_DESCRIPTION',
                'USER_TYPE_ID' => 'string',
                'SORT' => 500,
                'MANDATORY' => 'N',
                "EDIT_FORM_LABEL" => ['ru'=>'Описание', 'en'=>'Description'],
                "LIST_COLUMN_LABEL" => ['ru'=>'Описание', 'en'=>'Description'],
                "LIST_FILTER_LABEL" => ['ru'=>'Описание', 'en'=>'Description'],
                "ERROR_MESSAGE" => ['ru'=>'', 'en'=>''],
                "HELP_MESSAGE" => ['ru'=>'', 'en'=>''],
            ],
        ];

        // сохраним все свойства
        $arSavedFieldsRes = [];
        foreach($arCartFields as $arCartField) {
            $obUserField  = new CUserTypeEntity;
            $ID = $obUserField->Add($arCartField);
            // $arSavedFieldsRes[] = $ID;
        }

        return true;
    }

    public function UnInstallDB($arParams = [])
    {

        // получим ID нужного hl-блока
        $hlblockId = HL\HighloadBlockTable::query()
            ->setSelect(['ID'])
            ->setFilter(['=NAME' => 'Releases'])
            ->exec()
            ->fetch()['ID']
        ;

        // удалим его
        HL\HighloadBlockTable::delete($hlblockId);

        return true;
    }
}
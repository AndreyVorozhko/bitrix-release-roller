<?php

use Bitrix\Highloadblock as HL;
use Bitrix\Main\Loader;

Loader::includeModule('highloadblock');

class HlBlockHelper
{
    private $hlblockId;
    private $dataClass;

    public function __construct()
    {
        $this->hlblockId = $this->getHlBlocId();

        $hlblock = HL\HighloadBlockTable::getById($this->hlblockId)->fetch();
        $this->dataClass = HL\HighloadBlockTable::compileEntity($hlblock)->getDataClass();
    }

    /**
     * Геттер для id сущности (инфблока)
     * @return int
     */
    public function getEntityId(): int
    {
        return (int) $this->hlblockId;
    }

    public function isExists()
    {
        return (bool) $this->hlblockId;
    }

    public function getDataClass()
    {
        return $this->dataClass;
    }

    private function getHlBlocId()
    {
        return HL\HighloadBlockTable::query()
            ->setSelect(['ID'])
            ->setFilter(['=NAME' => HLBLOCK_NAME])
            ->setCacheTtl(3600)
            ->exec()
            ->fetch()['ID']
        ;
    }
}
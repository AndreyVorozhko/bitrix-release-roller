<?php


use Bitrix\Main\Application;
use Vorozhko\Roller\THelper;

class ReleaseRoller
{
    use THelper;

    private \Bitrix\Main\HttpRequest $request;
    private ZipArchive $releaseZip;
    private \HlBlockHelper $hlHelper;

    public function __construct(
        \Bitrix\Main\HttpRequest $request,
        \HlBlockHelper $hlHelper
    )
    {
        $this->request = $request;
        $this->hlHelper = $hlHelper;
        $this->validate();
    }

    public function roll(): void
    {
        $this->backup();
        $this->extractFiles();
        $this->logReleaseToHl();

        echo (new CAdminMessage([
            'TYPE' => 'OK',
            'MESSAGE' => (bool) $this->request->getPostList()->get('is_backup') ? 'Бекап успешно раскатан!' : 'Релиз успешно раскатан',
            'DETAILS' => (bool) $this->request->getPostList()->get('is_backup') ? 'Все должно вернуться назад!' : 'Не забудьте, что у вас есть скачанный бекап. Сохраните его!',
        ]))->Show();
    }

    private function validate(): void
    {
        if (empty($this->request->getPost('release_num'))) {
            throw new \RuntimeException('Не заполнено обязательное поле "Номер релиза"');
        }
        $this->validateZip();
    }

    private function backup(): void
    {
        // Не делаем бекап если раскатываем ранее сделанный бекап
        if ((bool) $this->request->getPostList()->get('is_backup')) {
            return;
        }
        $releaseNum = $this->request->getPostList()->get('release_num');
        $backupFilePath = CTempFile::GetAbsoluteRoot() . DIRECTORY_SEPARATOR . "backup_{$releaseNum}_"
            . (new DateTime())->format('Y-m-d_H-i-s') . '.zip';
        $serverRoot = Application::getDocumentRoot();

        $backupZip = new ZipArchive();
        if (! $backupZip->open($backupFilePath, ZipArchive::CREATE)) {
            throw new \RuntimeException("Не удалось создать новый архив для бекапа.");
        }

        // создаем архив бекапа
        // додавляем файлы по путям от корня проекта
        foreach ($this->getZipFileList() as $file) {

            $serverFile = $serverRoot . DIRECTORY_SEPARATOR . $file;
            if (is_file($serverFile)) {

                if(! is_readable($serverFile)) {

                }

                $addResult = $backupZip->addFile($serverFile, $file);
                if (! $addResult) {
                    throw new \RuntimeException("Не удается забекапить файл $serverFile");
                }
            }
        }
        $backupZip->close();

        $backupFileLink = substr($backupFilePath, strlen($serverRoot));

        // Обязательно надо протестировать полученный архив
        if (! $this->testZip($backupFilePath)) {
            throw new \Exception('Архив бекапа поврежден поврежден.');
        }

        // Это все, чтобы автоматически скачать браузером бекап
        $this->toBrowserDownload($backupFileLink);
    }

    private function extractFiles(): void
    {
        // распаковываем релиз в корень проекта
        $this->releaseZip->extractTo(
            Application::getDocumentRoot()
        );
        $this->releaseZip->close();
    }

    private function logReleaseToHl(): void
    {
        $hlDataClass = $this->hlHelper->getDataClass();
        $formValues = $this->request->getValues();

        $data = [
            'UF_RELEASE_NUM' => $formValues['release_num'],
            'UF_RELEASE_TIME' => new \Bitrix\Main\Type\DateTime(),
            'UF_RELEASE_USER_ID' => \Bitrix\Main\Engine\CurrentUser::get()->getId(),
            'UF_RELEASE_IS_BACKUP' => $formValues['is_backup'],
            'UF_RELEASE_DESCRIPTION' => nl2br($formValues['description']),
        ];

        $hlDataClass::add($data);
    }

    private function validateZip(): void
    {
        $uploadedFileInfo = $this->request->getFileList()->toArray()['file_'];

        $za = new ZipArchive();
        $status = $za->open($uploadedFileInfo['tmp_name']);

        $this->releaseZip = $za;

        if ($uploadedFileInfo['error'] === 4) {
            throw new \RuntimeException('Не загружен файл релиза');
        }

        if ($uploadedFileInfo['size'] === 0) {
            throw new \RuntimeException('Загружен файл нулевой длины');
        }

        if ($status !== true) {

            if ($status === ZipArchive::ER_NOZIP) {
                throw new \RuntimeException('Загруженный файл не является zip-архивом');
            }

            throw new \RuntimeException("Что-то не так с вашим файлом релиза (архивом), посмотрите в справке ZipArchive php код: $status");
        }

    }

    private function getZipFileList(): array
    {

        $zipFileList = [];
        for( $i = 0; $i < $this->releaseZip->numFiles; $i++ ){
            $path = $this->releaseZip->getNameIndex($i);
            if (! $this->isDir($path)) {
                $zipFileList[] = $path;
            }
        }
        return $zipFileList;
    }

}
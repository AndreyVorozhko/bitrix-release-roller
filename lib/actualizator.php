<?php

namespace Vorozhko\Roller;

use Bitrix\Main\Application;
use CAdminMessage;

class Actualizator
{
    use THelper;

    private $request;
    private $routes;
    private const RESTRICTIONS = [
        DIRECTORY_SEPARATOR,
        DIRECTORY_SEPARATOR . 'bitrix',
        DIRECTORY_SEPARATOR . 'upload',
    ];

    public function __construct()
    {
        $this->request = Application::getInstance()->getContext()->getRequest();
    }

    public function build(): void
    {
        $this->parseRoutes();
        $this->cleanRoutes();
        $this->formatRoutes();
        $this->makeZip();

        echo (new CAdminMessage([
            'TYPE' => 'OK',
            'MESSAGE' => 'Актуализация собрана',
            'DETAILS' => 'И передана в браузер на скачивание',
        ]))->Show();
    }

    private function parseRoutes(): void
    {
        $routes = $this->request->get('routes');
        if (empty($routes)) {
            throw new \RuntimeException('Не указаны маршруты');
        }
        $this->routes = explode(PHP_EOL, $this->request->get('routes'));
    }

    private function cleanRoutes(): void
    {
        $this->routes = array_map(static function ($route){
            return trim($route);
        }, $this->routes);

        $this->routes = array_filter(
            $this->routes,
            static function ($route){
            return ! empty($route);
        });

        // если массив с маршрутами оказывается пустой то надо кинуть исключение
        if (empty($this->routes)) {
            throw new \RuntimeException('Маршруты не найдены');
        }
    }

    private function formatRoutes(): void
    {
        // заменим любые слеши на DIRECTORY_SEPARATOR
        $this->routes = array_map(static function ($route){
            return str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $route);
        }, $this->routes);

        // если первым символом не идет DIRECTORY_SEPARATOR, то добавим его
        $this->routes = array_map(static function ($route){
            return $route[0] === DIRECTORY_SEPARATOR ? $route : DIRECTORY_SEPARATOR . $route ;
        }, $this->routes);

        // конечные DIRECTORY_SEPARATOR у директорий - уберем
        $this->routes = array_map(static function ($route){
            return rtrim($route, DIRECTORY_SEPARATOR);
        }, $this->routes);

        // уберем дубли
        $this->routes = array_unique($this->routes);

        // проверим запреты
        $restrictions = array_intersect($this->routes, self::RESTRICTIONS);
        if (! empty($restrictions)) {
            throw new \RuntimeException('Я отказываюсь качать вот это: ' . implode(', ', $restrictions));
        }
    }

    private function makeZip(): void
    {
        $archiveFilePath = \CTempFile::GetAbsoluteRoot() . DIRECTORY_SEPARATOR . "actual_"
            . (new \DateTime())->format('Y-m-d_H-i-s') . '.zip';

        $za = new \ZipArchive();
        $za->open($archiveFilePath, \ZipArchive::CREATE);

        $projectRoot = Application::getDocumentRoot();

        array_map(static function ($route) use ($za, $projectRoot){
            $fullPath = "$projectRoot$route";
            if (is_dir($fullPath)) {
                $files = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($fullPath), \RecursiveIteratorIterator::LEAVES_ONLY );

                foreach ($files as $name => $file) {
                    // Skip directories (they would be added automatically)
                    if (!$file->isDir()) {
                        // Get real and relative path for current file
                        $filePath = $file->getRealPath();
                        $relativePath = substr($filePath, strlen($projectRoot) + 1);

                        if (! is_readable($filePath)) {
                            throw new \RuntimeException("Файл $relativePath недоступен для чтения.");
                        }

                        // Add current file to archive
                        $za->addFile($filePath, $relativePath);
                    }
                }
            } elseif (is_file($fullPath)) {
                $relativePath = substr($fullPath, strlen($projectRoot) + 1);
                if (! is_readable($fullPath)) {
                    throw new \RuntimeException("Файл $relativePath недоступен для чтения.");
                }
                $za->addFile($fullPath, $relativePath);
            } else {
                throw new \RuntimeException("По маршруту $route в проекте ничего не найдено.");
            }
        }, $this->routes);

        // Обязательно надо протестировать полученный архив
        if (! $this->testZip($archiveFilePath)) {
            throw new \RuntimeException('Результирующий архив поврежден');
        }

        // тут +1 уже не надо, так как формируем ссылку на закачку, начинающуюся с /
        $this->toBrowserDownload(substr($archiveFilePath, strlen($projectRoot)));
    }
}

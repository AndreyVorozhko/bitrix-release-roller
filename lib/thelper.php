<?php


namespace Vorozhko\Roller;

// Трейты тоже можно автолоадить в модулях из папки lib
use ZipArchive;

trait THelper
{
    // Является ли путь в архиве директорией
    // Заканчивается ли на /
    public function isDir($path): bool
    {
        return substr($path,-1) === '/';
    }

    // Это все, чтобы автоматически скачать браузером файл
    public function toBrowserDownload($fileLink): void {

        $jsScript = <<<jsScript
                <script>
                function get_file_url(url) {
                    var link_url = document.createElement("a");
                    
                    link_url.download = url.substring((url.lastIndexOf("/") + 1), url.length);
                    link_url.href = url;
                    document.body.appendChild(link_url);
                    link_url.click();
                    document.body.removeChild(link_url);
                    delete link_url;
                }
                get_file_url("$fileLink");
                </script>      
                jsScript
        ;
        echo $jsScript;
    }

    public function testZip($path): bool
    {
        $zip = new ZipArchive();
        $res = $zip->open($path, ZipArchive::CHECKCONS);
        return $res === TRUE;
    }
}

<?php

include 'E:\dev\adv\Krugozor\Framework\Utility\Upload\File.php';

use Krugozor\Framework\Utility\Upload\File;

$error = '';
if (!empty($_FILES['file'])) {
    $upload = new File($_FILES['file']);
    //$upload->setAllowableMimeType('image/jpeg', 'image/gif', 'image/png', 'image/pjpeg', 'image/x-png');
    $upload->setFileNameAsUnique();
    $upload->setMaxFileSize('1M');

    if ($upload->isFileUpload()) {
        if ($mime_error = $upload->hasMimeTypeErrors()) {
            $error = 'Загруженный файл имеет недопустимый mime-тип ' . $mime_error;
        } else if ($size_errror = $upload->hasFileSizeErrors()) {
            $error = 'Загруженный файл имеет недопустимый размер.<br>' .
                'Допустимый размер указанный через класс (в Кб): ' . $upload->getMaxFileSize('K') . '<br>' .
                'Допустимый размер указанный в php.ini upload_max_filesize: ' . ini_get('upload_max_filesize') . '<br>' .
                'Ошибка общего размера (в Мб): ' . File::getStringFromBytes($size_errror, 'M');
        } else {
            $upload->copy('E:\111');
        }
    } else if ($size_errror = $upload->hasFileSizeErrorFormSize()) {
        $error = 'Файл не был загружен. Размер загружаемого файла превысил значение MAX_FILE_SIZE, указанное в HTML-форме.';
    } else {
        $error = 'Файл не был загружен.';
    }
}
?>

<p><?=$error?></p>

<form enctype="multipart/form-data" method="post">
    <input type="hidden" name="MAX_FILE_SIZE" value="123">
    <input name="file" type="file">
    <input type="submit">
</form>
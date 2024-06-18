<?php

namespace Krugozor\Framework\Utility\Upload;

use finfo;
use Krugozor\Framework\Statical\ErrorLog;
use Krugozor\Framework\Statical\Strings;
use RuntimeException;

/**
 * Класся для загрузки файлов.
 */
class File
{
    /**
     * Массив, содержащий информацию о загруженном файле.
     * Аналог содержания одного элемента $_FILES.
     *
     * @var array
     */
    protected array $file;

    /**
     * Максимально-допустимый размер загружаемого файла, в байтах.
     *
     * @var int
     */
    protected int $max_file_size;

    /**
     * Массив допустимых MIME-типов загружаемого файла.
     *
     * @var array
     */
    protected array $allowable_mime_types = [];

    /**
     * Будущее расширение загруженного файла. Опционально.
     *
     * @var string|null
     */
    protected ?string $file_ext = null;

    /**
     * Будущее имя загружаемого файла. Опционально.
     *
     * @var string
     */
    protected string $file_name;

    /**
     * Директория, в которую будет загружен файл.
     *
     * @var string
     */
    protected string $file_directory;

    /**
     * Максимально-допустимая длинна имени файла.
     *
     * @var int
     */
    protected const FILE_NAME_MAX_LENGTH = 255;

    /**
     * Максимально-допустимая длинна расширения файла.
     *
     * @var int
     */
    protected const FILE_EXT_MAX_LENGTH = 10;

    /**
     * Принимает значение одного элемента массива $_FILES
     *
     * @param array
     */
    public function __construct(array $file)
    {
        $this->file = $file;
    }

    /**
     * Устанавливает будущее имя загружаемого файла.
     * Если имя не указывается, файл будет сохранён с оригинальным именем.
     *
     * @param string $file_name
     * @return File
     */
    public function setFileName(string $file_name): self
    {
        $this->file_name = self::deleteBadSymbols(trim($file_name));

        if (strlen($this->file_name) > self::FILE_NAME_MAX_LENGTH) {
            $this->file_name = substr($this->file_name, 0, self::FILE_NAME_MAX_LENGTH);
        }

        return $this;
    }

    /**
     * Устанавливает будущее имя загружаемого файла как хэш md5 от случйной строки.
     *
     * @return File
     */
    public function setFileNameAsUnique(): self
    {
        $this->file_name = md5(microtime(true) . $this->file['tmp_name']);

        return $this;
    }

    /**
     * Устанавливает расширение загружаемого файла.
     * Если расширение не указывается, файл будет сохранён с оригинальным расширением.
     *
     * @param string $file_ext
     * @return File
     */
    public function setFileExt(string $file_ext): self
    {
        $this->file_ext = self::deleteBadSymbols(trim($file_ext));

        if (strlen($this->file_ext) > self::FILE_EXT_MAX_LENGTH) {
            $this->file_ext = substr($this->file_ext, 0, self::FILE_EXT_MAX_LENGTH);
        }

        return $this;
    }

    /**
     * Устанавливает максимально-допустимый размер файла в байтах.
     * Значение $size может быть любой формой представления человекопонятной
     * размерности данных, принятых в PHP, например: 8M, 2B, 30G
     *
     * @param string $size
     * @return File
     */
    public function setMaxFileSize(string $size): self
    {
        $this->max_file_size = Strings::getBytesFromString($size);

        return $this;
    }

    /**
     * Устанавливает допустимые mime-типы загружаемых файлов.
     *
     * @param string ...$args допустимые mime-типы
     * @return static
     */
    public function setAllowableMimeType(string ...$args): static
    {
        foreach ($args as $arg) {
            if (!in_array($arg, $this->allowable_mime_types)) {
                $this->allowable_mime_types[] = strtolower($arg);
            }
        }

        return $this;
    }

    /**
     * Возвращает TRUE, если файл был загружен на сервер и FALSE в противном случае.
     *
     * @return bool
     */
    public function isFileUpload(): bool
    {
        return $this->file['error'] === UPLOAD_ERR_OK && is_uploaded_file($this->file['tmp_name']);
    }

    /**
     * Копирует загруженный файл в директорию $directory.
     *
     * @param string $directory
     * @return File
     */
    public function copy(string $directory): self
    {
        if (!is_dir($directory)) {
            throw new RuntimeException(__METHOD__ . ': Не найдена указанная директория для загрузки: ' . $directory);
        }

        $this->file_directory = rtrim($directory, '/\\') . DIRECTORY_SEPARATOR;

        if ($this->file['error'] === UPLOAD_ERR_OK && file_exists($this->file['tmp_name']) && $this->isFileUpload()) {
            $pathinfo = pathinfo($this->file['name']);

            // Если расширение файла явно объявленно, то оно станет раширением файла при копировании.
            // В противном случае расширением будет оригинальное расширение загруженного файла
            $this->setFileExt($this->file_ext ?: (isset($pathinfo['extension']) ? strtolower($pathinfo['extension']) : ''));

            // Имя файла будет либо оригинальное, либо объявленное пользователем.
            $this->setFileName($this->file_name ?: $pathinfo['filename']);

            if (!@move_uploaded_file($this->file['tmp_name'], $this->file_directory . $this->getFileNameWithExtension())) {
                ErrorLog::write(sprintf(
                    '%s: Ошибка копирования файла в директорию %s', __METHOD__, $this->file_directory
                ));
                throw new RuntimeException(sprintf(
                    '%s: Ошибка копирования файла: %s',
                    __METHOD__,
                    print_r(error_get_last(), true)
                ));
            }
        }

        return $this;
    }

    /**
     * Метод проверки MIME-типа файла.
     * Возвращает mime_type в случае ошибки и false в противном случае.
     *
     * @return false|string
     */
    public function hasMimeTypeErrors(): false|string
    {
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mime_type = $finfo->file($this->file['tmp_name']);

        if (!empty($mime_type) && $this->allowable_mime_types && !in_array($mime_type, $this->allowable_mime_types)) {
            return $mime_type;
        }

        return false;
    }

    /**
     * @return bool
     */
    public function hasFileSizeErrors(): bool
    {
        return
            // Размер принятого файла превысил максимально допустимый размер,
            // который задан директивой upload_max_filesize конфигурационного файла php.ini.
            $this->file['error'] === UPLOAD_ERR_INI_SIZE ||
            // Значение: 2; Размер загружаемого файла превысил значение MAX_FILE_SIZE, указанное в HTML-форме.
            $this->file['error'] === UPLOAD_ERR_FORM_SIZE ||
            // Собственная валидация на размер файла, на случай, если серверные настройки максимального размера файла
            // значительно превысят настройку максимального размера файла, прописанные в приложении.
            !is_null($this->max_file_size) && $this->max_file_size < $this->file['size'];
    }

    /**
     * Возвращает int, если размер загружаемого файла превысил
     * значение MAX_FILE_SIZE, указанное в HTML-форме.
     *
     * @return int
     */
/*    public function hasFileSizeErrorFormSize(): bool
    {
        return $this->file['error'] === UPLOAD_ERR_FORM_SIZE;
    }*/

    /**
     * Возвращает имя файла с расширением.
     *
     * @return string
     */
    public function getFileNameWithExtension(): string
    {
        return $this->file_name . ($this->file_ext ? '.' . $this->file_ext : '');
    }

    /**
     * Возвращает имя файла без расширения.
     *
     * @return string
     */
    public function getFileNameWithoutExtension(): string
    {
        return $this->file_name;
    }

    /**
     * Удаляет из строки все служебные символы Windows и Unix.
     *
     * @param string
     * @return string
     */
    private static function deleteBadSymbols(string $in): string
    {
        return preg_replace('~[/\:*?"<>|]~', '', $in);
    }
}
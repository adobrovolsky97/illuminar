<?php

namespace Adobrovolsky97\Illuminar\StorageDrivers;

use Exception;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Filesystem\Filesystem;

/**
 * Class FilesystemStorageDriver
 */
class FilesystemStorageDriver implements StorageDriverInterface
{
    /**
     * @var Filesystem
     */
    private Filesystem $filesystem;

    /**
     * @var string
     */
    private string $directoryName;

    /**
     * @param Filesystem $filesystem
     */
    public function __construct(Filesystem $filesystem)
    {
        $this->filesystem = $filesystem;
        $this->directoryName = rtrim(config('illuminar.storage.path'), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
    }

    /**
     * Save data to filesystem storage
     *
     * @param array $data
     * @return void
     * @throws Exception
     */
    public function save(array $data): void
    {
        $this->createDirectoryIfNotExists();

        $this->filesystem->put($this->getFilePath(), json_encode($data));
    }

    /**
     * Append data to storage
     *
     * @param array $data
     * @return void
     * @throws Exception
     */
    public function append(array $data): void
    {
        $this->createDirectoryIfNotExists();

        // Get existing data
        $existingData = [];
        if ($this->filesystem->exists($this->getFilePath())) {
            $existingData = json_decode($this->filesystem->get($this->getFilePath()), true);
            if (!is_array($existingData)) {
                $existingData = [];
            }
        }

        // Merge existing data with new data
        $mergedData = array_merge($existingData, $data);
        $this->filesystem->put($this->getFilePath(), json_encode($mergedData));
    }

    /**
     * Get data from filesystem storage
     *
     * @return array
     * @throws FileNotFoundException
     */
    public function getData(): array
    {
        if (!$this->filesystem->exists($this->getFilePath())) {
            return [];
        }

        return json_decode($this->filesystem->get($this->getFilePath()), true);
    }

    /**
     * Get file path
     *
     * @return string
     */
    private function getFilePath(): string
    {
        return $this->directoryName . config('illuminar.storage.filename') . '.json';
    }

    /**
     * Create directory if not exists
     *
     * @throws Exception
     */
    private function createDirectoryIfNotExists(): void
    {
        // Create directory if not exists and add .gitignore file
        if (!$this->filesystem->isDirectory($this->directoryName)) {
            if (!$this->filesystem->makeDirectory($this->directoryName, 0777, true)) {
                throw new Exception("Cannot create directory '$this->directoryName'..");
            }

            $this->filesystem->put($this->directoryName . '.gitignore', "*\n!.gitignore\n");
        }
    }
}

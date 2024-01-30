<?php

namespace Adobrovolsky97\Illuminar\StorageDrivers;

use Exception;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Filesystem\Filesystem;
use Throwable;

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
     * Clear storage
     *
     * @return void
     */
    public function clear(): void
    {
        if ($this->filesystem->exists($this->getFilePath())) {
            $this->filesystem->delete($this->getFilePath());
        }
    }

    /**
     * Handle entry
     *
     * @param array $data
     * @return void
     */
    public function saveEntry(array $data): void
    {
        if (!isset($data['uuid'])) {
            return;
        }

        try {
            $existingData = $this->getData();

            $index = array_search($data['uuid'], array_column($existingData, 'uuid'));

            if ($index === false) {
                $this->append([$data]);
                return;
            }

            $existingData[$index] = array_merge($existingData[$index], $data);
            $this->save($existingData);
        } catch (Throwable $exception) {

        }
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
     * Save data to filesystem storage
     *
     * @param array $data
     * @return void
     * @throws Exception
     */
    private function save(array $data): void
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
    private function append(array $data): void
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
        $mergedData = array_merge($data, $existingData);

        // Limit entries
        $mergedData = array_slice($mergedData, 0, config('illuminar.storage.limit'));

        $this->filesystem->put($this->getFilePath(), json_encode($mergedData));
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

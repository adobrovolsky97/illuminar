<?php

namespace Adobrovolsky97\Illuminar;

use Adobrovolsky97\Illuminar\Factories\StorageDriverFactory;
use Adobrovolsky97\Illuminar\Payloads\Payload;
use Exception;

/**
 * Illuminar data collector
 *
 * Class DataCollector
 */
final class DataCollector
{
    /**
     * Data collected by watchers
     *
     * @var array
     */
    private static array $batch = [];

    /**
     * @var bool
     */
    private static bool $fake = false;

    /**
     * Not allowed to create instance of this class
     */
    private function __construct()
    {
    }

    /**
     * @return void
     */
    public static function fake(): void
    {
        self::$fake = true;
    }

    /**
     * Add data to collector
     *
     * @param Payload $payload
     * @return void
     */
    public static function addToBatch(Payload $payload): void
    {
        if ($payload->isMutable()) {
            self::$batch[$payload->getUuid()] = &$payload;
            return;
        }

        self::$batch[$payload->getUuid()] = $payload->toArray();
    }

    /**
     * Store data to a configured storage
     *
     * @throws Exception
     */
    public static function storeData(): void
    {
        if (empty(self::$batch) || self::$fake) {
            return;
        }

        self::prepareDataForStoring();

        self::resetKeys();
        app(StorageDriverFactory::getDriverForConfig())->save(self::$batch);
        self::reset();
    }

    /**
     * Append data to file without rewriting it
     *
     * @return void
     * @throws Exception
     */
    public static function appendData(): void
    {
        if (empty(self::$batch) || self::$fake) {
            return;
        }

        self::prepareDataForStoring();

        self::resetKeys();
        app(StorageDriverFactory::getDriverForConfig())->append(self::$batch);
        self::reset();
    }

    /**
     * Reset batch
     *
     * @return void
     */
    public static function reset(): void
    {
        self::$batch = [];
    }

    /**
     * Get batch
     *
     * @return array
     */
    public static function getBatch(): array
    {
        return self::$batch;
    }

    /**
     * Reset keys
     *
     * @return void
     */
    private static function resetKeys(): void
    {
        self::$batch = array_values(self::$batch);
    }

    /**
     * Prepare data for storing
     *
     * @return void
     */
    private static function prepareDataForStoring(): void
    {
        foreach (self::$batch as $id => &$payload) {

            $payload = $payload instanceof Payload ? $payload->toArray() : $payload;

            if (empty($payload['uuid'])) {
                unset(self::$batch[$id]);
            }
        }
    }
}

<?php

namespace Adobrovolsky97\Illuminar\Console;

use Adobrovolsky97\Illuminar\Factories\StorageDriverFactory;
use Illuminate\Console\Command;

/**
 * php artisan illuminar:clear
 */
class ClearCommand extends Command
{
    protected $signature = 'illuminar:clear';

    protected $description = 'Remove all entries stored by Illuminar';

    public function handle(): int
    {
        StorageDriverFactory::getDriverForConfig()->clear();

        $this->components->info('Illuminar entries cleared.');

        return self::SUCCESS;
    }
}

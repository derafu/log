<?php

declare(strict_types=1);

/**
 * Derafu: Log - PHP Logging Library.
 *
 * Copyright (c) 2025 Esteban De La Fuente Rubio / Derafu <https://www.derafu.dev>
 * Licensed under the MIT License.
 * See LICENSE file for more details.
 */

namespace Derafu\Log\Service;

use Derafu\Container\Contract\JournalInterface;
use Derafu\Log\Log;
use Monolog\Handler\AbstractProcessingHandler;
use Monolog\LogRecord as MonologLogRecord;

/**
 * Class to handle the log messages.
 */
class JournalHandler extends AbstractProcessingHandler
{
    /**
     * Constructor of the class.
     *
     * @param JournalInterface $journal
     */
    public function __construct(private readonly JournalInterface $journal)
    {
    }

    /**
     * Adds a log record to the storage.
     *
     * @param MonologLogRecord $logRecord
     */
    public function write(MonologLogRecord $logRecord): void
    {
        if (!($logRecord instanceof Log)) {
            $processor = new Processor();
            $logRecord = $processor($logRecord);
        }

        $this->journal->add($logRecord);
    }
}

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

use Derafu\Log\Log;
use Monolog\LogRecord as MonologLogRecord;
use Monolog\Processor\ProcessorInterface;

/**
 * Processor of the log record.
 */
class Processor implements ProcessorInterface
{
    /**
     * Processor of the log record.
     *
     * @param MonologLogRecord $logRecord Original log record.
     * @return MonologLogRecord Converted log record.
     */
    public function __invoke(MonologLogRecord $logRecord): MonologLogRecord
    {
        // Extract caller if exists.
        $context = $logRecord->context;
        $caller = $context['__caller'] ?? null;
        unset($context['__caller']);

        // Create instance of LogRecord.
        $log = new Log(
            datetime: $logRecord->datetime,
            channel: $logRecord->channel,
            level: $logRecord->level,
            message: $logRecord->message,
            context: $context,
            extra: $logRecord->extra,
            formatted: $logRecord->formatted
        );

        // Assign additional log record data.
        $log->code = $context['code'] ?? $logRecord->level->value;
        $log->caller = $caller;

        // Return the custom log record.
        return $log;
    }
}

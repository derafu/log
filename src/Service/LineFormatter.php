<?php

declare(strict_types=1);

/**
 * Derafu: Log - PHP Logging Library.
 *
 * Copyright (c) 2025 Esteban De La Fuente Rubio / Derafu <https://www.derafu.org>
 * Licensed under the MIT License.
 * See LICENSE file for more details.
 */

namespace Derafu\Log\Service;

use Derafu\Log\Contract\LogInterface;
use Monolog\Formatter\LineFormatter as MonologLineFormatter;
use Monolog\LogRecord as MonologLogRecord;

/**
 * Class to generate a custom representation of the log message.
 *
 * It uses the official Monolog LineFormatter and adds the caller to the end
 * of the line.
 */
class LineFormatter extends MonologLineFormatter
{
    /**
     * Formats the log record.
     *
     * @param MonologLogRecord $logRecord Log record.
     * @return string Formatted log record.
     */
    public function format(MonologLogRecord $logRecord): string
    {
        $message = parent::format($logRecord);

        if ($logRecord instanceof LogInterface && $logRecord->getCaller() !== null) {
            return sprintf(
                '%s %s.',
                $message,
                (string) $logRecord->getCaller(),
            );
        }

        return $message;
    }
}

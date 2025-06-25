<?php

declare(strict_types=1);

/**
 * Derafu: Log - PHP Logging Library.
 *
 * Copyright (c) 2025 Esteban De La Fuente Rubio / Derafu <https://www.derafu.dev>
 * Licensed under the MIT License.
 * See LICENSE file for more details.
 */

namespace Derafu\Log;

use InvalidArgumentException;
use LogicException;
use Monolog\Level as MonologLevel;
use Psr\Log\LogLevel as PsrLogLevel;

/**
 * Class for the levels of the log.
 */
class Level
{
    /**
     * Detailed debug information.
     *
     * @var int
     */
    public const DEBUG = MonologLevel::Debug->value;

    /**
     * Events of interest.
     *
     * Examples:
     *
     *   - User login registration.
     *   - SQL queries registration.
     *
     * @var int
     */
    public const INFO = MonologLevel::Info->value;

    /**
     * Uncommon events records.
     *
     * @var int
     */
    public const NOTICE = MonologLevel::Notice->value;

    /**
     * Events that are not errors.
     *
     * Examples:
     *
     *   - Use of deprecated APIs.
     *   - Use of discouraged ("poor") APIs.
     *   - Undesirable or "improper" uses that are not necessarily bad but could
     *     be made better.
     *
     * @var int
     */
    public const WARNING = MonologLevel::Warning->value;

    /**
     * Runtime errors.
     *
     * @var int
     */
    public const ERROR = MonologLevel::Error->value;

    /**
     * Critical conditions.
     *
     * @var int
     */
    public const CRITICAL = MonologLevel::Critical->value;

    /**
     * Alerts that require immediate action.
     *
     * Examples:
     *
     *   - Application down.
     *   - Tests that do not pass.
     *   - Data sources unavailable.
     *
     * This must raise an alert that must reach "someone" to be reviewed ASAP (as
     * soon as possible).
     *
     * @var int
     */
    public const ALERT = MonologLevel::Alert->value;

    /**
     * Urgent alerts.
     *
     * @var int
     */
    public const EMERGENCY = MonologLevel::Emergency->value;

    /**
     * Mapping of log levels from different systems or sources to the levels
     * used by the log service of the library.
     *
     * @var array<int|string,int>
     */
    private const LEVELS = [
        // Logs de PHP / RFC5424 (?).
        LOG_DEBUG => self::DEBUG,
        LOG_INFO => self::INFO,
        LOG_NOTICE => self::NOTICE,
        LOG_WARNING => self::WARNING,
        LOG_ERR => self::ERROR,
        LOG_CRIT => self::CRITICAL,
        LOG_ALERT => self::ALERT,
        LOG_EMERG => self::EMERGENCY,

        // Logs de PSR-3.
        PsrLogLevel::DEBUG => self::DEBUG,
        PsrLogLevel::INFO => self::INFO,
        PsrLogLevel::NOTICE => self::NOTICE,
        PsrLogLevel::WARNING => self::WARNING,
        PsrLogLevel::ERROR => self::ERROR,
        PsrLogLevel::CRITICAL => self::CRITICAL,
        PsrLogLevel::ALERT => self::ALERT,
        PsrLogLevel::EMERGENCY => self::EMERGENCY,
    ];

    /**
     * Code of the level.
     *
     * @var int
     */
    private int $code;

    /**
     * Constructor of the entity.
     *
     * @param int|string $code
     */
    public function __construct(int|string $code)
    {
        $this->setCode($code);
    }

    /**
     * Assigns the level code of the log.
     *
     * @param int|string $code Level code in any supported format.
     * @return static
     * @throws LogicException When the level is an unsupported string.
     */
    private function setCode(int|string $code): static
    {
        if (isset(self::LEVELS[$code])) {
            $this->code = self::LEVELS[$code];

            return $this;
        }

        if (is_string($code)) {
            throw new LogicException(sprintf(
                'The log level %s is not supported.',
                $code
            ));
        }

        $this->code = $code;

        return $this;
    }

    /**
     * Returns the level code.
     *
     * The code is returned normalized.
     *
     * @return integer
     */
    public function getCode(): int
    {
        return $this->code;
    }

    /**
     * Returns the level name.
     *
     * @return string Level name.
     */
    public function getName(): string
    {
        return $this->getMonologLevel()->name;
    }

    /**
     * Returns the "enum" instance of the level.
     *
     * @return MonologLevel
     */
    public function getMonologLevel(): MonologLevel
    {
        $level = MonologLevel::tryFrom($this->code);

        if ($level === null) {
            throw new InvalidArgumentException(sprintf(
                'The log level code %d is invalid as a Monolog level.',
                $this->code
            ));
        }

        return $level;
    }
}

<?php

declare(strict_types=1);

/**
 * Derafu: Log - PHP Logging Library.
 *
 * Copyright (c) 2025 Esteban De La Fuente Rubio / Derafu <https://www.derafu.org>
 * Licensed under the MIT License.
 * See LICENSE file for more details.
 */

namespace Derafu\Log;

/**
 * Class that represents who called the log.
 */
class Caller
{
    /**
     * File where the log was called.
     *
     * @var string
     */
    public string $file;

    /**
     * Line of the file where the log was called.
     *
     * @var int
     */
    public int $line;

    /**
     * Method that called the log.
     *
     * @var string
     */
    public string $function;

    /**
     * Class of the method that called the log.
     *
     * @var string
     */
    public string $class;

    /**
     * Type of call (static or object instantiated).
     *
     * @var string
     */
    public string $type;

    /**
     * Constructor of the class.
     *
     * @param string $file
     * @param int $line
     * @param string $function
     * @param string $class
     * @param string $type
     */
    public function __construct(
        string $file,
        int $line,
        string $function,
        string $class,
        string $type
    ) {
        $this->file = $file;
        $this->line = $line;
        $this->function = $function;
        $this->class = $class;
        $this->type = $type;
    }

    /**
     * Magic method to get who called the log as a string from the attributes of
     * the instance of this class.
     *
     * @return string Who called the log formatted as a string.
     */
    public function __toString(): string
    {
        return sprintf(
            'in %s on line %d, called by %s%s%s()',
            $this->file,
            $this->line,
            $this->class,
            $this->type,
            $this->function
        );
    }
}

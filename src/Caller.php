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

/**
 * Class that represents who called the log.
 */
class Caller
{
    /**
     * File where the log was called.
     *
     * @var string|null
     */
    public ?string $file;

    /**
     * Line of the file where the log was called.
     *
     * @var int|null
     */
    public ?int $line;

    /**
     * Method that called the log.
     *
     * @var string|null
     */
    public ?string $function;

    /**
     * Class of the method that called the log.
     *
     * @var string|null
     */
    public ?string $class;

    /**
     * Type of call (static or object instantiated).
     *
     * @var string|null
     */
    public ?string $type;

    /**
     * Constructor of the class.
     *
     * @param string|null $file
     * @param int|null $line
     * @param string|null $function
     * @param string|null $class
     * @param string|null $type
     */
    public function __construct(
        ?string $file,
        ?int $line,
        ?string $function,
        ?string $class,
        ?string $type
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
        $parts = [];

        if ($this->file !== null) {
            $location = 'in ' . $this->file;
            if ($this->line !== null) {
                $location .= ' on line ' . $this->line;
            }
            $parts[] = $location;
        }

        if ($this->function !== null) {
            $parts[] = 'called by ' . ($this->class ?? '') . ($this->type ?? '') . $this->function . '()';
        }

        return implode(', ', $parts);
    }
}

<?php

declare(strict_types=1);

/**
 * Derafu: Log - PHP Logging Library.
 *
 * Copyright (c) 2026 Esteban De La Fuente Rubio / Derafu <https://www.derafu.dev>
 * Licensed under the MIT License.
 * See LICENSE file for more details.
 */

namespace Derafu\TestsLog;

use Derafu\Log\Level;
use Derafu\Translation\Contract\TranslatableInterface;
use InvalidArgumentException;
use LogicException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Throwable;

/**
 * A level that is not supported is a translatable error that says which one it
 * is.
 */
#[CoversClass(Level::class)]
final class LevelTest extends TestCase
{
    public function testAStringThatIsNotALevelIsATranslatableError(): void
    {
        $exception = null;
        try {
            new Level('bogus');
        } catch (Throwable $e) {
            $exception = $e;
        }

        $this->assertInstanceOf(LogicException::class, $exception);
        $this->assertInstanceOf(TranslatableInterface::class, $exception);
        $this->assertSame('The log level bogus is not supported.', $exception->getMessage());
    }

    public function testACodeThatIsNotAMonologLevelIsATranslatableError(): void
    {
        $level = new Level(12345);

        $exception = null;
        try {
            $level->getMonologLevel();
        } catch (Throwable $e) {
            $exception = $e;
        }

        $this->assertInstanceOf(InvalidArgumentException::class, $exception);
        $this->assertInstanceOf(TranslatableInterface::class, $exception);
        $this->assertSame('The log level code 12345 is invalid as a Monolog level.', $exception->getMessage());
    }
}

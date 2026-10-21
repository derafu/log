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

use DateTimeImmutable;
use Derafu\Log\Caller;
use Derafu\Log\Log;
use Derafu\Log\Service\JournalHandler;
use Derafu\Log\Service\Logger;
use Derafu\Log\Service\Processor;
use Derafu\Log\Translation\LogTranslationResourceProvider;
use Derafu\Translation\Exception\Runtime\TranslatableUnexpectedValueException as UnexpectedValueException;
use Derafu\Translation\TranslatorFactory;
use Monolog\Formatter\LineFormatter;
use Monolog\Formatter\NormalizerFormatter;
use Monolog\Level;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use stdClass;

#[CoversClass(Log::class)]
#[UsesClass(Caller::class)]
#[UsesClass(JournalHandler::class)]
#[UsesClass(Logger::class)]
#[UsesClass(Processor::class)]
#[UsesClass(LogTranslationResourceProvider::class)]
final class LogFormattedRecordTest extends TestCase
{
    private function record(mixed $formatted): Log
    {
        return new Log(
            datetime: new DateTimeImmutable(),
            channel: 'test',
            level: Level::Info,
            message: 'Original message',
            formatted: $formatted,
        );
    }

    /** @return iterable<string, array{string|null}> */
    public static function textualValues(): iterable
    {
        yield 'null' => [null];
        yield 'empty string' => [''];
        yield 'zero string' => ['0'];
        yield 'text' => ['Formatted message'];
    }

    #[DataProvider('textualValues')]
    public function testBothGettersPreserveTextAndNull(?string $formatted): void
    {
        $record = $this->record($formatted);

        $this->assertSame($formatted, $record->getFormattedRecord());
        $this->assertSame($formatted, $record->getFormattedMessage());
        $this->assertSame($formatted, $record->formatted);
    }

    /** @return iterable<string, array{mixed}> */
    public static function nonTextualValues(): iterable
    {
        yield 'structured array' => [['message' => 'Formatted message', 'context' => ['id' => 123]]];
        yield 'empty array' => [[]];
        yield 'object' => [(object) ['message' => 'Formatted message']];
        yield 'empty object' => [new stdClass()];
        yield 'integer' => [42];
        yield 'zero' => [0];
        yield 'float' => [1.5];
        yield 'true' => [true];
        yield 'false' => [false];
    }

    #[DataProvider('nonTextualValues')]
    public function testTheOriginalValueRemainsAvailableWhenTheTextGetterRejectsIt(mixed $formatted): void
    {
        $record = $this->record($formatted);
        $this->assertSame($formatted, $record->getFormattedRecord());

        try {
            $record->getFormattedMessage();
            $this->fail('A non-textual formatted value must be rejected by the text getter.');
        } catch (UnexpectedValueException $exception) {
            $type = get_debug_type($formatted);
            $this->assertSame("The formatted log message must be string|null; $type given.", $exception->getMessage());
            $translator = TranslatorFactory::create('es', resourceProviders: [new LogTranslationResourceProvider()]);
            $this->assertSame("El mensaje de log formateado debe ser string|null; se recibió $type.", $exception->trans($translator));
        }

        $this->assertSame($formatted, $record->getFormattedRecord());
        $this->assertSame($formatted, $record->formatted);
        $this->assertSame('Original message', $record->getMessage());
    }

    public function testARealNormalizerFormatterKeepsItsStructuredOutputAvailable(): void
    {
        $formatter = new NormalizerFormatter();
        $logger = new Logger(formatter: $formatter);
        $logger->info('Structured message', ['id' => 123]);
        $records = $logger->getJournal()->all();
        $this->assertCount(1, $records);
        $record = $records[0];
        $this->assertInstanceOf(Log::class, $record);
        $formatted = $record->getFormattedRecord();
        $this->assertIsArray($formatted);
        $this->assertSame('Structured message', $formatted['message']);
        $this->assertSame(['id' => 123], $formatted['context']);
        $this->assertSame($formatter->format($record), $formatted);

        $this->expectException(UnexpectedValueException::class);
        $this->expectExceptionMessage('The formatted log message must be string|null; array given.');
        $record->getFormattedMessage();
    }

    public function testARealTextFormatterKeepsTheExistingTextGetterWorking(): void
    {
        $logger = new Logger(formatter: new LineFormatter('%message%'));
        $logger->info('Textual message');
        $records = $logger->getJournal()->all();
        $this->assertCount(1, $records);
        $record = $records[0];
        $this->assertInstanceOf(Log::class, $record);

        $this->assertSame('Textual message', $record->getFormattedMessage());
        $this->assertSame('Textual message', $record->getFormattedRecord());
    }
}

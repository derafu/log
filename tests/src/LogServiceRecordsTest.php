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
use Derafu\Log\Contract\LogInterface;
use Derafu\Log\Level;
use Derafu\Log\Log;
use Derafu\Log\Service\JournalHandler;
use Derafu\Log\Service\Logger;
use Derafu\Log\Service\LogService;
use Derafu\Log\Service\Processor;
use Derafu\Log\Translation\LogTranslationResourceProvider;
use Derafu\TestsLog\Fixture\LogRecordView;
use Derafu\Translation\Exception\Runtime\TranslatableUnexpectedValueException as UnexpectedValueException;
use Derafu\Translation\TranslatorFactory;
use Monolog\Level as MonologLevel;
use Monolog\LogRecord;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use stdClass;

#[CoversClass(LogService::class)]
#[UsesClass(Caller::class)]
#[UsesClass(Level::class)]
#[UsesClass(Log::class)]
#[UsesClass(JournalHandler::class)]
#[UsesClass(Logger::class)]
#[UsesClass(Processor::class)]
#[UsesClass(LogTranslationResourceProvider::class)]
final class LogServiceRecordsTest extends TestCase
{
    private function service(): LogService
    {
        $logger = new Logger();
        $logger->info('First');
        $logger->error('Second');
        $logger->error('Third');
        $logger->warning('Fourth');

        return new LogService($logger);
    }

    /** @return iterable<string, array{mixed}> */
    public static function invalidRecords(): iterable
    {
        yield 'null' => [null];
        yield 'boolean' => [false];
        yield 'integer' => [123];
        yield 'float' => [1.5];
        yield 'string' => ['not a record'];
        yield 'array' => [['message' => 'not a record']];
        yield 'object' => [new stdClass()];
        yield 'plain Monolog record' => [new LogRecord(new DateTimeImmutable(), 'test', MonologLevel::Info, 'Plain')];
    }

    /** @return iterable<string, array{string, mixed}> */
    public static function invalidOperations(): iterable
    {
        foreach (['logs', 'filtered logs', 'clear', 'flush', 'filtered flush'] as $operation) {
            foreach (self::invalidRecords() as $type => [$record]) {
                yield "$operation/$type" => [$operation, $record];
            }
        }
    }

    #[DataProvider('invalidOperations')]
    public function testInvalidRecordsAreRejectedWithoutChangingTheJournal(string $operation, mixed $record): void
    {
        $service = $this->service();
        $journal = $service->logger()->getJournal();
        $journal->set('broken', $record);
        $before = $journal->all();

        try {
            match ($operation) {
                'logs' => $service->logs(),
                'filtered logs' => $service->logs(Level::ERROR),
                'clear' => $service->clear(Level::ERROR),
                'flush' => $service->flush(),
                'filtered flush' => $service->flush(Level::ERROR),
                default => $this->fail('Unknown test operation: ' . $operation),
            };
            $this->fail('A journal element that is not a log record must be rejected.');
        } catch (UnexpectedValueException $exception) {
            $type = get_debug_type($record);
            $this->assertSame('The log journal record at key "broken" must implement LogInterface; ' . $type . ' given.', $exception->getMessage());
            $translator = TranslatorFactory::create('es', resourceProviders: [new LogTranslationResourceProvider()]);
            $this->assertSame('El registro del journal de log con clave "broken" debe implementar LogInterface; se recibió ' . $type . '.', $exception->trans($translator));
        }

        $this->assertSame($before, $journal->all());
    }

    public function testTheExceptionIdentifiesAnInvalidRecordWithANumericKey(): void
    {
        $service = $this->service();
        $service->logger()->getJournal()->add(null);

        $this->expectException(UnexpectedValueException::class);
        $this->expectExceptionMessage('The log journal record at key "4" must implement LogInterface; null given.');
        $service->logs();
    }

    #[DataProvider('invalidRecords')]
    public function testClearingTheEntireJournalStillAllowsRecoveryFromInvalidRecords(mixed $record): void
    {
        $service = $this->service();
        $journal = $service->logger()->getJournal();
        $journal->set('broken', $record);

        $service->clear();

        $this->assertSame([], $journal->all());
        $service->logger()->info('Recovered');
        $records = $service->logs();
        $this->assertCount(1, $records);
        $this->assertSame('Recovered', $records[0]->getMessage());
    }

    #[TestWith([false])]
    #[TestWith([true])]
    public function testInterfaceOnlyRecordsKeepTheirKeysOrderAndFiltering(bool $newFirst): void
    {
        $service = $this->service();
        $journal = $service->logger()->getJournal();
        $views = [];
        foreach ($journal->all() as $key => $record) {
            $this->assertInstanceOf(LogInterface::class, $record);
            $views['entry' . $key] = new LogRecordView($record);
        }
        $journal->clear();
        foreach ($views as $key => $view) {
            $journal->set($key, $view);
        }

        $this->assertSame($newFirst ? array_reverse($views) : $views, $service->logs(newFirst: $newFirst));
        $errors = [$views['entry1'], $views['entry2']];
        $expectedErrors = $newFirst ? array_reverse($errors) : $errors;
        $this->assertSame($expectedErrors, $service->logs(Level::ERROR, $newFirst));
        $this->assertSame($views, $journal->all());
        $this->assertSame($expectedErrors, $service->flush(Level::ERROR, $newFirst));
        $remaining = [$views['entry0'], $views['entry3']];
        $this->assertSame($remaining, $journal->all());
        $this->assertSame($newFirst ? array_reverse($remaining) : $remaining, $service->flush(newFirst: $newFirst));
        $this->assertSame([], $journal->all());
    }

    public function testFilteredClearRemovesOnlyTheRequestedLevelFromInterfaceOnlyRecords(): void
    {
        $service = $this->service();
        $journal = $service->logger()->getJournal();
        $views = [];
        foreach ($journal->all() as $record) {
            $this->assertInstanceOf(LogInterface::class, $record);
            $views[] = new LogRecordView($record);
        }
        $journal->clear();
        foreach ($views as $view) {
            $journal->add($view);
        }

        $service->clear(Level::ERROR);

        $this->assertSame([$views[0], $views[3]], $journal->all());
        $this->assertSame([], $service->logs(Level::ERROR));
    }

    public function testOperationsOnAnEmptyInitializedJournalStillReturnEmptyResults(): void
    {
        $service = $this->service();
        $service->clear();

        $this->assertSame([], $service->logs());
        $this->assertSame([], $service->logs(Level::ERROR));
        $this->assertSame([], $service->flush());
        $service->clear(Level::ERROR);
        $this->assertSame([], $service->logger()->getJournal()->all());
    }
}

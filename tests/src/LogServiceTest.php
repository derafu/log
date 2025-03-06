<?php

declare(strict_types=1);

/**
 * Derafu: Log - PHP Logging Library.
 *
 * Copyright (c) 2025 Esteban De La Fuente Rubio / Derafu <https://www.derafu.org>
 * Licensed under the MIT License.
 * See LICENSE file for more details.
 */

namespace Derafu\TestsLog;

use Derafu\Log\Caller;
use Derafu\Log\Contract\LoggerInterface;
use Derafu\Log\Contract\LogInterface;
use Derafu\Log\Contract\LogServiceInterface;
use Derafu\Log\Level;
use Derafu\Log\Log;
use Derafu\Log\Service\JournalHandler;
use Derafu\Log\Service\LineFormatter;
use Derafu\Log\Service\Logger;
use Derafu\Log\Service\LogService;
use Derafu\Log\Service\Processor;
use Monolog\Test\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use Psr\Log\LogLevel as PsrLogLevel;

#[CoversClass(LogService::class)]
#[CoversClass(Caller::class)]
#[CoversClass(Level::class)]
#[CoversClass(Log::class)]
#[CoversClass(JournalHandler::class)]
#[CoversClass(LineFormatter::class)]
#[CoversClass(Logger::class)]
#[CoversClass(Processor::class)]

class LogServiceTest extends TestCase
{
    private LogServiceInterface $logService;

    private LoggerInterface $logger;

    private static array $testCases = [
        'messages' => [
            Level::DEBUG => 'Test DEBUG message.',
            Level::INFO => 'Test INFO message.',
            Level::NOTICE => 'Test NOTICE message.',
            Level::WARNING => 'Test WARNING message.',
            Level::ERROR => 'Test ERROR message.',
            Level::CRITICAL => 'Test CRITICAL message.',
            Level::ALERT => 'Test ALERT message.',
            Level::EMERGENCY => 'Test EMERGENCY message.',
        ],
        'levels' => [
            'PHP' => [
                Level::DEBUG => LOG_DEBUG,
                Level::INFO => LOG_INFO,
                Level::NOTICE => LOG_NOTICE,
                Level::WARNING => LOG_WARNING,
                Level::ERROR => LOG_ERR,
                Level::CRITICAL => LOG_CRIT,
                Level::ALERT => LOG_ALERT,
                Level::EMERGENCY => LOG_EMERG,
            ],
            'PSR-3' => [
                Level::DEBUG => PsrLogLevel::DEBUG,
                Level::INFO => PsrLogLevel::INFO,
                Level::NOTICE => PsrLogLevel::NOTICE,
                Level::WARNING => PsrLogLevel::WARNING,
                Level::ERROR => PsrLogLevel::ERROR,
                Level::CRITICAL => PsrLogLevel::CRITICAL,
                Level::ALERT => PsrLogLevel::ALERT,
                Level::EMERGENCY => PsrLogLevel::EMERGENCY,
            ],
            'logService' => [
                Level::DEBUG => Level::DEBUG,
                Level::INFO => Level::INFO,
                Level::NOTICE => Level::NOTICE,
                Level::WARNING => Level::WARNING,
                Level::ERROR => Level::ERROR,
                Level::CRITICAL => Level::CRITICAL,
                Level::ALERT => Level::ALERT,
                Level::EMERGENCY => Level::EMERGENCY,
            ],
        ],
    ];

    protected function setUp(): void
    {
        $logger = new Logger(
            formatter: new LineFormatter()
        );
        $this->logService = new LogService($logger);
        $this->logger = $this->logService->logger();
    }

    public static function provideStandardLevelsAndMessages(): array
    {
        $cases = [];

        foreach (self::$testCases['messages'] as $level => $message) {
            $cases[$message . ' (' . $level . ')'] = [
                $level,
                $message,
            ];
        }

        return $cases;
    }

    public static function provideCustomLevelsAndMessages(): array
    {
        $cases = [];

        foreach (self::$testCases['levels'] as $levelType => $testCases) {
            foreach ($testCases as $id => $level) {
                $message = self::$testCases['messages'][$id];
                $cases[$message . ' (' . $levelType . ': ' . $level . ')'] = [
                    $level,
                    $message,
                ];
            }
        }

        return $cases;
    }

    #[DataProvider('provideStandardLevelsAndMessages')]
    public function testLogWithStandardLevelAndMessage(int $level, string $message): void
    {
        switch ($level) {
            case Level::EMERGENCY:
                $this->logger->emergency($message);
                break;
            case Level::ALERT:
                $this->logger->alert($message);
                break;
            case Level::CRITICAL:
                $this->logger->critical($message);
                break;
            case Level::ERROR:
                $this->logger->error($message);
                break;
            case Level::WARNING:
                $this->logger->warning($message);
                break;
            case Level::NOTICE:
                $this->logger->notice($message);
                break;
            case Level::INFO:
                $this->logger->info($message);
                break;
            case Level::DEBUG:
                $this->logger->debug($message);
                break;
        }

        $logs = $this->logService->logs();
        $this->assertCount(1, $logs);

        $logRecord = $logs[0];
        $this->assertInstanceOf(LogInterface::class, $logRecord);
        $this->assertSame($level, $logRecord->getCode());
        $this->assertSame($message, $logRecord->getMessage());
    }

    #[DataProvider('provideCustomLevelsAndMessages')]
    public function testLogWithCustomLevelAndMessage($level, $message): void
    {
        $this->logger->log($level, $message);

        $level = (new Level($level))->getCode();
        $logs = $this->logService->logs($level);

        $this->assertCount(1, $logs);

        $logRecord = $logs[0];
        $this->assertInstanceOf(LogInterface::class, $logRecord);
        $this->assertSame($level, $logRecord->getCode());
        $this->assertSame($message, $logRecord->getMessage());
    }

    public function testLogWithCaller(): void
    {

        $this->logger->error('With caller error message');

        $logs = $this->logService->logs(Level::ERROR);
        $this->assertCount(1, $logs);

        $logRecord = $logs[0];
        $this->assertInstanceOf(LogInterface::class, $logRecord);
        $this->assertSame(Level::ERROR, $logRecord->getCode());
        $this->assertSame('With caller error message', $logRecord->getMessage());
        $this->assertNotNull($logRecord->getCaller());
        $this->assertSame('testLogWithCaller', $logRecord->getCaller()->function);
    }

    public function testFlushLogs(): void
    {
        $this->logger->error('First error message');
        $this->logger->warning('First warning message');
        $this->logger->error('Second error message');

        $logs = $this->logService->flush(Level::ERROR);

        $this->assertCount(2, $logs);
        $this->assertSame('Second error message', $logs[0]->getMessage());
        $this->assertSame('First error message', $logs[1]->getMessage());

        $logsAfterFlush = $this->logService->logs(Level::ERROR);
        $this->assertEmpty($logsAfterFlush);
    }

    public function testClearLogs(): void
    {
        $this->logger->error('Error message');
        $this->logService->clear(Level::ERROR);

        $logs = $this->logService->logs(Level::ERROR);
        $this->assertEmpty($logs);
    }

    public function testClearAllLogs(): void
    {
        $this->logger->error('Error message');
        $this->logger->warning('Warning message');
        $this->logService->clear();

        $allLogs = $this->logService->logs();
        $this->assertEmpty($allLogs);
    }

    public function testLogWithContext(): void
    {
        $context = ['key' => 'value'];
        $this->logger->info('Info message with context', $context);

        $logs = $this->logService->logs(Level::INFO);
        $this->assertCount(1, $logs);

        $logRecord = $logs[0];
        $this->assertSame($context, $logRecord->getContext());
    }

    // Check that what is written to the log can be read back.
    public function testWriteReadAll(): void
    {
        // Log to be tested.
        $cases = [
            Level::ERROR => [
                'Error N° 1',
                'Ejemplo error dos',
                'Este es el tercer error',
            ],
            Level::WARNING => [
                'Este es el primer warning',
                'Un segundo warning',
                'El penúltimo warning',
                'El warning final (4to)',
            ],
        ];

        // Check that the log can be read in both orders (newest to oldest and
        // oldest to newest).
        foreach ([true, false] as $newFirst) {

            // Write to the log.
            foreach ($cases as $level => $messages) {
                foreach ($messages as $contextCode => $message) {
                    if ($level === Level::ERROR) {
                        $this->logger->error(
                            $message,
                            [
                                'code' => $contextCode,
                            ]
                        );
                    } elseif ($level === Level::WARNING) {
                        $this->logger->warning(
                            $message,
                            [
                                'code' => $contextCode,
                            ]
                        );
                    }
                }
            }

            // Check what was written to the log.
            foreach ($cases as $level => $messages) {
                $logs = $this->logService->flush($level, $newFirst);
                $this->assertNotEmpty($logs);
                $this->assertCount(count($cases[$level]), $logs);

                if ($newFirst) {
                    krsort($messages);
                }

                foreach ($messages as $contextCode => $message) {
                    $log = array_shift($logs);
                    $this->assertSame(
                        $contextCode,
                        $log->getContext()['code'] ?? null
                    );
                    $this->assertSame($message, $log->getMessage());
                }
            }
        }
    }
}

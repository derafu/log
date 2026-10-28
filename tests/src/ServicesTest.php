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

use Derafu\Log\Contract\LoggerInterface;
use Derafu\Log\Contract\LogServiceInterface;
use Derafu\Log\Level;
use Derafu\Log\Translation\LogTranslationResourceProvider;
use Derafu\Translation\Exception\Core\TranslatableLogicException;
use Monolog\Handler\StreamHandler;
use Monolog\Handler\TestHandler;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface as PsrLoggerInterface;
use Psr\Log\NullLogger;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\Translation\Translator;

/**
 * Exercises the real logger configuration and consumer handler contributions.
 */
#[CoversNothing]
final class ServicesTest extends TestCase
{
    private function container(string $configuration = 'services.yaml'): ContainerBuilder
    {
        $root = dirname(__DIR__, 2);
        $container = new ContainerBuilder();
        $loader = new YamlFileLoader($container, new FileLocator($root . '/tests/fixtures/config'));
        $loader->load($configuration);
        $this->assertArrayHasKey(
            LogTranslationResourceProvider::class,
            $container->findTaggedServiceIds('derafu_translation.resource_provider')
        );
        foreach ($container->getDefinitions() as $definition) {
            $definition->setPublic(true);
        }
        $container->compile();

        return $container;
    }

    public function testConfigurationConstructsTheTranslationProvider(): void
    {
        $provider = $this->container()->get(LogTranslationResourceProvider::class);
        $this->assertInstanceOf(LogTranslationResourceProvider::class, $provider);
        $directories = [...$provider->getDirectories()];
        $this->assertCount(1, $directories);
        $this->assertSame(realpath(dirname(__DIR__, 2) . '/resources/translations'), realpath($directories[0]));
    }

    public function testTaggedResourcesTranslateARealUnsupportedLevelException(): void
    {
        $translator = $this->container()->get('app.translator');
        $this->assertInstanceOf(Translator::class, $translator);
        try {
            new Level('unsupported');
            $this->fail('An unsupported log level was accepted.');
        } catch (TranslatableLogicException $exception) {
            $this->assertSame('El nivel de log unsupported no está soportado.', $exception->trans($translator));
        }
    }

    public function testFreshServiceCanReadClearAndFlushBeforeTheFirstWrite(): void
    {
        $container = $this->container();
        $service = $container->get(LogServiceInterface::class);
        $this->assertInstanceOf(LogServiceInterface::class, $service);
        $logger = $container->get(LoggerInterface::class);
        $this->assertInstanceOf(LoggerInterface::class, $logger);
        $this->assertSame($logger, $service->logger());
        $this->assertSame([], $service->logs());
        $journal = $logger->getJournal();
        $service->clear();
        $this->assertSame([], $service->flush());

        $logger->info('First message', ['id' => 7]);
        $this->assertSame($journal, $logger->getJournal());
        $records = $service->logs();
        $this->assertCount(1, $records);
        $this->assertSame('First message', $records[0]->getMessage());
        $this->assertSame(['id' => 7], $records[0]->getContext());
        $this->assertSame('derafu_lib', $records[0]->getChannel());
        $this->assertSame($records, $service->flush());
        $this->assertSame([], $service->logs());
        $this->assertFalse($container->has(PsrLoggerInterface::class));
    }

    public function testConfiguredHandlersReceiveRecordsAndKeepTheirOwnFormatters(): void
    {
        $container = $this->container('configured.yaml');
        $service = $container->get(LogServiceInterface::class);
        $this->assertInstanceOf(LogServiceInterface::class, $service);
        $service->logger()->warning('Check inventory', ['product' => 42]);

        $records = $service->logs();
        $this->assertCount(1, $records);
        $this->assertSame('integration', $records[0]->getChannel());
        $this->assertSame('journal:Check inventory', $records[0]->getFormattedMessage());
        $handler = $container->get(TestHandler::class);
        $this->assertInstanceOf(TestHandler::class, $handler);
        $captured = $handler->getRecords();
        $this->assertCount(1, $captured);
        $this->assertSame('Check inventory', $captured[0]->message);
        $this->assertSame(['product' => 42], $captured[0]->context);
        $this->assertSame('integration', $captured[0]->channel);

        $streamHandler = $container->get(StreamHandler::class);
        $this->assertInstanceOf(StreamHandler::class, $streamHandler);
        $stream = $streamHandler->getStream();
        $this->assertIsResource($stream);
        rewind($stream);
        $output = stream_get_contents($stream);
        $this->assertStringContainsString('integration.WARNING: Check inventory', $output);
        $this->assertStringNotContainsString('journal:', $output);
    }

    public function testApplicationPsrLoggerIsPreserved(): void
    {
        $container = $this->container('application-logger.yaml');
        $applicationLogger = $container->get(PsrLoggerInterface::class);
        $this->assertInstanceOf(NullLogger::class, $applicationLogger);
        $service = $container->get(LogServiceInterface::class);
        $this->assertInstanceOf(LogServiceInterface::class, $service);
        $this->assertNotSame($applicationLogger, $service->logger());
        $applicationLogger->info('Application message');
        $this->assertSame([], $service->flush());
        $service->logger()->info('Derafu message');
        $this->assertCount(1, $service->logs());
        $this->assertSame('Derafu message', $service->logs()[0]->getMessage());
    }
}

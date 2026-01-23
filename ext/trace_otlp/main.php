<?php

declare(strict_types=1);

namespace Shimmie2;

use Symfony\Component\Console\Input\InputOption;

final class TraceOTLP extends Extension
{
    public const KEY = "trace_otlp";
    private static ?string $traceFile = null;
    private static int $traceThreshold = 0;

    #[EventListener]
    public function onInitExt(InitExtEvent $event): void
    {
        self::$traceFile = Ctx::$config->get(OTLPCommonConfig::HOST);
        self::$traceThreshold = Ctx::$config->get(TraceOTLPConfig::TRACE_THRESHOLD);
        if (@$_GET["trace"] === "on") {
            self::$traceThreshold = 0;
        }

        $event->add_shutdown_handler(function () {
            $dur = (ftime() - $_SERVER["REQUEST_TIME_FLOAT"]) * 1000;
            if (
                // If tracing is enabled
                self::$traceFile !== null
                // And we took a long time
                && ($dur > self::$traceThreshold)
                // Ignore upload because that always takes forever and isn't worth tracing
                && ($_SERVER["REQUEST_URI"] ?? "") !== "/upload"
            ) {
                Ctx::$tracer->endAllSpans();
                Ctx::$tracer->flushTraces(self::$traceFile);
            }
        });
    }

    #[EventListener]
    public function onCliGen(CliGenEvent $event): void
    {
        $definition = $event->app->getDefinition();
        $definition->addOption(new InputOption(
            '--trace',
            '-t',
            InputOption::VALUE_REQUIRED,
            'Log a performance trace to the given file'
        ));
    }

    #[EventListener]
    public function onCliRun(CliRunEvent $event): void
    {
        self::$traceFile = $event->input->getParameterOption(
            ['--trace', '-t'],
            Ctx::$config->get(OTLPCommonConfig::HOST)
        );
        if (self::$traceFile !== null) {
            self::$traceThreshold = 0; // Always trace if --trace is passed
        }
    }
}

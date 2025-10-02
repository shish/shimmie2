<?php

declare(strict_types=1);

namespace Shimmie2;

use Symfony\Component\Console\Input\InputOption;

final class TraceChrome extends Extension
{
    public const KEY = "trace_chrome";
    private static ?string $traceFile = null;
    private static int $traceThreshold = 0;

    public function onInitExt(InitExtEvent $event): void
    {
        self::$traceFile = Ctx::$config->get(TraceChromeConfig::TRACE_FILE);
        self::$traceThreshold = Ctx::$config->get(TraceChromeConfig::TRACE_THRESHOLD);
        if (@$_GET["trace"] === "on") {
            self::$traceThreshold = 0;
        }

        $event->add_shutdown_handler(function () {
            if (
                // If tracing is enabled
                self::$traceFile !== null
                // And we took a long time
                && (ftime() - $_SERVER["REQUEST_TIME_FLOAT"] > self::$traceThreshold)
                // Ignore upload because that always takes forever and isn't worth tracing
                && ($_SERVER["REQUEST_URI"] ?? "") !== "/upload"
            ) {
                Ctx::$tracer->flush(self::$traceFile);
            }
        });
    }

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

    public function onCliRun(CliRunEvent $event): void
    {
        self::$traceFile = $event->input->getParameterOption(['--trace', '-t'], null);
        self::$traceThreshold = 0; // Always trace if --trace is passed
    }
}

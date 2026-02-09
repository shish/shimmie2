<?php

declare(strict_types=1);

namespace Shimmie2;

/**
 * Send an event to all registered Extensions.
 *
 * @template T of Event
 * @param T $event
 * @return T
 */
function send_event(Event $event): Event
{
    return Ctx::$event_bus->send_event($event);
}

final class EventBus
{
    /** @var array<class-string<Event>, list<array{0: Extension, 1: string}>> $event_listeners */
    private readonly array $event_listeners;
    public int $event_count = 0;
    private ?float $deadline = null;

    public function __construct()
    {
        $span = Ctx::$tracer->startSpan("Load Event Listeners");

        $ver = \Safe\preg_replace("/[^a-zA-Z0-9\.]/", "_", SysConfig::getVersion());
        $key = md5(Extension::get_enabled_extensions_as_string());

        $speed_hax = Ctx::$config->get(SetupConfig::CACHE_EVENT_LISTENERS);
        $cache_path = Filesystem::data_path("cache/event_listeners/el.$ver.$key.php");
        if ($speed_hax && $cache_path->exists()) {
            $this->event_listeners = require($cache_path->str());
        } else {
            $this->event_listeners = $this->calc_event_listeners();

            if ($speed_hax) {
                $cache_path->put_contents($this->dump_event_listeners());
                opcache_reset();
            }
        }

        if (ini_get('max_execution_time')) {
            $this->set_timeout((int)ini_get('max_execution_time') - 3);
        }

        $span->end();
    }

    /**
     * Check which extensions are installed, supported, and active;
     * scan them for on<EventName>() functions; return a map of them
     *`
     * @return array<class-string<Event>, list<array{0: Extension, 1: string}>> $event_listeners
     */
    private function calc_event_listeners(): array
    {
        $event_listeners_with_ids = [];

        foreach (Extension::get_subclasses() as $class) {
            $extension = $class->newInstance();

            // skip extensions which don't support our current database
            if (!ExtensionInfo::get_all()[$extension::KEY]->is_supported()) {
                continue;
            }

            foreach ($class->getMethods() as $method) {
                foreach ($method->getAttributes(EventListener::class) as $attribute) {
                    $attribute = $attribute->newInstance();

                    if (isset($attribute->event)) {
                        $event = $attribute->event;
                    } elseif ($method->getNumberOfParameters() > 0) {
                        $event = $method->getParameters()[0]->getType()?->__toString();
                    }

                    if (!isset($event)) {
                        continue;
                    }

                    /** @var class-string<Event> $event */

                    $pos = $attribute->priority * 100;
                    while (isset($event_listeners_with_ids[$event][$pos])) {
                        $pos += 1;
                    }
                    $event_listeners_with_ids[$event][$pos] = [$extension, $method->name];
                }
            }
        }

        $event_listeners = [];
        foreach ($event_listeners_with_ids as $event => $listeners) {
            ksort($listeners);
            $event_listeners[$event] = array_values($listeners);
        }

        return $event_listeners;
    }

    private function namespaced_class_name(string $class): string
    {
        return str_replace("Shimmie2\\", "", $class);
    }

    private function dump_event_listeners(): string
    {
        $header = "<"."?php\nnamespace Shimmie2;\n";

        $classes = [];
        $listeners_str = "return [\n";
        foreach ($this->event_listeners as $event => $listeners) {
            $t = [];
            foreach ($listeners as $_id => $listener) {
                [$listener, $method] = $listener;

                // @phpstan-ignore-next-line
                $class_name = $this->namespaced_class_name(get_class($listener));
                $classes[] = $class_name;
                $t[] = "[\$$class_name, '$method']";
            }
            $listeners_str .= "\t'$event' => [" . implode(", ", $t) . "],\n";
        }
        $listeners_str .= "];\n";

        $classes_str = "";
        foreach (array_unique($classes) as $scn) {
            $classes_str .= "\$$scn = new $scn();\n";
        }
        $classes_str .= "\n";

        return $header . $classes_str . $listeners_str;
    }

    public function set_timeout(?int $timeout): void
    {
        if ($timeout) {
            $this->deadline = ftime() + $timeout;
        } else {
            $this->deadline = null;
        }
        set_time_limit(is_null($timeout) ? 0 : $timeout);
    }

    /**
     * Send an event to all registered Extensions.
     *
     * @template T of Event
     * @param T $event
     * @return T
     */
    public function send_event(Event $event): Event
    {
        $event_class = \get_class($event);
        if (!isset($this->event_listeners[$event_class])) {
            return $event;
        }

        $event_name = $this->namespaced_class_name($event_class);
        $sEvent = Ctx::$tracer->startSpan($event_name);

        foreach ($this->event_listeners[$event_class] as [$listener, $method]) {
            if ($this->deadline && ftime() > $this->deadline) {
                throw new TimeoutException("Timeout while sending $event_name");
            }
            // @phpstan-ignore-next-line
            $sListener = Ctx::$tracer->startSpan($this->namespaced_class_name(\get_class($listener)));
            $listener->$method($event);
            $sListener->end();
            if ($event->stop_processing === true) {
                break;
            }
        }
        $this->event_count++;
        $sEvent->end();

        return $event;
    }
}

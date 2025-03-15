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
    global $_shm_event_bus;
    return $_shm_event_bus->send_event($event);
}

function shm_set_timeout(?int $timeout): void
{
    global $_shm_event_bus;
    $_shm_event_bus->set_timeout($timeout);
}

final class EventBus
{
    /** @var array<string, list<Extension>> $event_listeners */
    private array $event_listeners = [];
    public int $event_count = 0;
    private ?float $deadline = null;

    public function __construct()
    {
        global $config, $_tracer;

        $_tracer->begin("Load Event Listeners");

        $ver = \Safe\preg_replace("/[^a-zA-Z0-9\.]/", "_", SysConfig::getVersion());
        $key = md5(Extension::get_enabled_extensions_as_string());

        $speed_hax = ($config->get_bool(SetupConfig::CACHE_EVENT_LISTENERS));
        $cache_path = Filesystem::data_path("cache/event_listeners/el.$ver.$key.php");
        if ($speed_hax && $cache_path->exists()) {
            $this->event_listeners = require_once($cache_path);
        } else {
            $this->event_listeners = $this->calc_event_listeners();

            if ($speed_hax) {
                $cache_path->put_contents($this->dump_event_listeners());
            }
        }

        if (ini_get('max_execution_time')) {
            $this->set_timeout((int)ini_get('max_execution_time') - 3);
        }

        $_tracer->end();
    }

    /**
     * Check which extensions are installed, supported, and active;
     * scan them for on<EventName>() functions; return a map of them
     *
     * @return array<string, list<Extension>> $event_listeners
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

            foreach (get_class_methods($extension) as $method) {
                if (substr($method, 0, 2) === "on") {
                    $event = substr($method, 2) . "Event";
                    $pos = $extension->get_priority() * 100;
                    while (isset($event_listeners_with_ids[$event][$pos])) {
                        $pos += 1;
                    }
                    $event_listeners_with_ids[$event][$pos] = $extension;
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
            foreach ($listeners as $id => $listener) {
                $class_name = $this->namespaced_class_name(get_class($listener));
                $classes[] = $class_name;
                $t[] = "\$".$class_name;
            }
            $listeners_str .= "\t'$event' => [" . implode(", ", $t) . "],\n";
        }
        $listeners_str .= "];\n";

        $classes_str = "";
        foreach (array_unique($classes) as $scn) {
            $classes_str .= "\$$scn = new $scn(); ";
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
        global $_tracer, $tracer_enabled;

        $event_name = $this->namespaced_class_name(get_class($event));
        if (!isset($this->event_listeners[$event_name])) {
            return $event;
        }

        // send_event() is performance sensitive, and with the number
        // of times tracer gets called the time starts to add up
        if ($tracer_enabled) {
            $_tracer->begin($event_name);
        }
        $method_name = "on".str_replace("Event", "", $event_name);
        foreach ($this->event_listeners[$event_name] as $listener) {
            if ($this->deadline && ftime() > $this->deadline) {
                throw new TimeoutException("Timeout while sending $event_name");
            }
            if ($tracer_enabled) {
                $_tracer->begin($this->namespaced_class_name(get_class($listener)));
            }
            if (method_exists($listener, $method_name)) {
                $listener->$method_name($event);
            }
            if ($tracer_enabled) {
                $_tracer->end();
            }
            if ($event->stop_processing === true) {
                break;
            }
        }
        $this->event_count++;
        if ($tracer_enabled) {
            $_tracer->end();
        }

        return $event;
    }
}

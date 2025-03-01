<?php

declare(strict_types=1);

namespace Shimmie2;

class TimeoutException extends \RuntimeException
{
}


/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *\
* Event API                                                                 *
\* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */

/** @private */
global $_shm_event_listeners;
$_shm_event_listeners = [];

function _load_event_listeners(): void
{
    global $_shm_event_listeners, $config;

    $ver = \Safe\preg_replace("/[^a-zA-Z0-9\.]/", "_", VERSION);
    $key = md5(Extension::get_enabled_extensions_as_string());

    $speed_hax = (Extension::is_enabled(SpeedHaxInfo::KEY) && $config->get_bool(SpeedHaxConfig::CACHE_EVENT_LISTENERS));
    $cache_path = data_path("cache/event_listeners/el.$ver.$key.php");
    if ($speed_hax && file_exists($cache_path)) {
        $_shm_event_listeners = require_once($cache_path);
    } else {
        $_shm_event_listeners = _calc_event_listeners();

        if ($speed_hax) {
            file_put_contents($cache_path, _dump_event_listeners($_shm_event_listeners));
        }
    }
}

/**
 * Check which extensions are installed, supported, and active;
 * scan them for on<EventName>() functions; return a map of them
 *
 * @return array<string, array<int, Extension>> $event_listeners
 */
function _calc_event_listeners(): array
{
    $event_listeners = [];

    foreach (get_subclasses_of(Extension::class) as $class) {
        /** @var Extension $extension */
        $extension = new $class();

        // skip extensions which don't support our current database
        if (!$extension->info->is_supported()) {
            continue;
        }

        foreach (get_class_methods($extension) as $method) {
            if (substr($method, 0, 2) == "on") {
                $event = substr($method, 2) . "Event";
                $pos = $extension->get_priority() * 100;
                while (isset($event_listeners[$event][$pos])) {
                    $pos += 1;
                }
                $event_listeners[$event][$pos] = $extension;
            }
        }
    }

    return $event_listeners;
}

function _namespaced_class_name(string $class): string
{
    return str_replace("Shimmie2\\", "", $class);
}

/**
 * Dump the event listeners to a file for faster loading.
 *
 * @param array<string, array<int, Extension>> $event_listeners
 */
function _dump_event_listeners(array $event_listeners): string
{
    $header = "<"."?php\nnamespace Shimmie2;\n";

    $classes = [];
    $listeners_str = "return array(\n";
    foreach ($event_listeners as $event => $listeners) {
        $listeners_str .= "\t'$event' => array(\n";
        foreach ($listeners as $id => $listener) {
            $class_name = _namespaced_class_name(get_class($listener));
            $classes[] = $class_name;
            $listeners_str .= "\t\t$id => \$".$class_name.",\n";
        }
        $listeners_str .= "\t),\n";
    }
    $listeners_str .= ");\n";

    $classes_str = "";
    foreach ($classes as $scn) {
        $classes_str .= "\$$scn = new $scn(); ";
    }
    $classes_str .= "\n";

    return $header . $classes_str . $listeners_str;
}


/** @private */
global $_shm_event_count;
$_shm_event_count = 0;
$_shm_timeout = null;

function shm_set_timeout(?int $timeout = null): void
{
    global $_shm_timeout;
    if ($timeout) {
        $_shm_timeout = ftime() + $timeout;
    } else {
        $_shm_timeout = null;
    }
    set_time_limit(is_null($timeout) ? 0 : $timeout);
}

if (ini_get('max_execution_time')) {
    shm_set_timeout((int)ini_get('max_execution_time') - 3);
}

/**
 * Send an event to all registered Extensions.
 *
 * @template T of Event
 * @param T $event
 * @return T
 */
function send_event(Event $event): Event
{
    global $tracer_enabled;

    global $_shm_event_listeners, $_shm_event_count, $_tracer, $_shm_timeout;
    $event_name = _namespaced_class_name(get_class($event));
    if (!isset($_shm_event_listeners[$event_name])) {
        return $event;
    }
    $method_name = "on".str_replace("Event", "", $event_name);

    // send_event() is performance sensitive, and with the number
    // of times tracer gets called the time starts to add up
    if ($tracer_enabled) {
        $_tracer->begin(get_class($event));
    }
    // SHIT: https://bugs.php.net/bug.php?id=35106
    $my_event_listeners = $_shm_event_listeners[$event_name];
    ksort($my_event_listeners);

    foreach ($my_event_listeners as $listener) {
        if ($_shm_timeout && ftime() > $_shm_timeout) {
            throw new TimeoutException("Timeout while sending $event_name");
        }
        if ($tracer_enabled) {
            $_tracer->begin(get_class($listener));
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
    $_shm_event_count++;
    if ($tracer_enabled) {
        $_tracer->end();
    }

    return $event;
}

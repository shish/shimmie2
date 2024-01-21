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
    global $_shm_event_listeners;

    $ver = preg_replace("/[^a-zA-Z0-9\.]/", "_", VERSION);
    $key = md5(Extension::get_enabled_extensions_as_string());

    $cache_path = data_path("cache/event_listeners/el.$ver.$key.php");
    if (SPEED_HAX && file_exists($cache_path)) {
        require_once($cache_path);
    } else {
        _set_event_listeners();

        if (SPEED_HAX) {
            _dump_event_listeners($_shm_event_listeners, $cache_path);
        }
    }
}

function _clear_cached_event_listeners(): void
{
    if (file_exists(data_path("cache/shm_event_listeners.php"))) {
        unlink(data_path("cache/shm_event_listeners.php"));
    }
}

function _set_event_listeners(): void
{
    global $_shm_event_listeners;
    $_shm_event_listeners = [];

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
                while (isset($_shm_event_listeners[$event][$pos])) {
                    $pos += 1;
                }
                $_shm_event_listeners[$event][$pos] = $extension;
            }
        }
    }
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
function _dump_event_listeners(array $event_listeners, string $path): void
{
    $p = "<"."?php\nnamespace Shimmie2;\n";

    foreach (get_subclasses_of(Extension::class) as $class) {
        $scn = _namespaced_class_name($class);
        $p .= "\$$scn = new $scn(); ";
    }

    $p .= "\$_shm_event_listeners = array(\n";
    foreach ($event_listeners as $event => $listeners) {
        $p .= "\t'$event' => array(\n";
        foreach ($listeners as $id => $listener) {
            $p .= "\t\t$id => \$"._namespaced_class_name(get_class($listener)).",\n";
        }
        $p .= "\t),\n";
    }
    $p .= ");\n";

    file_put_contents($path, $p);
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

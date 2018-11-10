<?php
/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *\
* Event API                                                                 *
\* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */

/** @private */
global $_shm_event_listeners;
$_shm_event_listeners = array();

function _load_event_listeners() {
	global $_shm_event_listeners, $_shm_ctx;

	$_shm_ctx->log_start("Loading extensions");

	$cache_path = data_path("cache/shm_event_listeners.php");
	if(COMPILE_ELS && file_exists($cache_path)) {
		require_once($cache_path);
	}
	else {
		_set_event_listeners();

		if(COMPILE_ELS) {
			_dump_event_listeners($_shm_event_listeners, $cache_path);
		}
	}

	$_shm_ctx->log_endok();
}

function _set_event_listeners() {
	global $_shm_event_listeners;
	$_shm_event_listeners = array();

	foreach(get_declared_classes() as $class) {
		$rclass = new ReflectionClass($class);
		if($rclass->isAbstract()) {
			// don't do anything
		}
		elseif(is_subclass_of($class, "Extension")) {
			/** @var Extension $extension */
			$extension = new $class();

			// skip extensions which don't support our current database
			if(!$extension->is_live()) continue;

			foreach(get_class_methods($extension) as $method) {
				if(substr($method, 0, 2) == "on") {
					$event = substr($method, 2) . "Event";
					$pos = $extension->get_priority() * 100;
					while(isset($_shm_event_listeners[$event][$pos])) {
						$pos += 1;
					}
					$_shm_event_listeners[$event][$pos] = $extension;
				}
			}
		}
	}
}

/**
 * @param array $event_listeners
 * @param string $path
 */
function _dump_event_listeners($event_listeners, $path) {
	$p = "<"."?php\n";

	foreach(get_declared_classes() as $class) {
		$rclass = new ReflectionClass($class);
		if($rclass->isAbstract()) {}
		elseif(is_subclass_of($class, "Extension")) {
			$p .= "\$$class = new $class(); ";
		}
	}

	$p .= "\$_shm_event_listeners = array(\n";
	foreach($event_listeners as $event => $listeners) {
		$p .= "\t'$event' => array(\n";
		foreach($listeners as $id => $listener) {
			$p .= "\t\t$id => \$".get_class($listener).",\n";
		}
		$p .= "\t),\n";
	}
	$p .= ");\n";

	$p .= "?".">";
	file_put_contents($path, $p);
}

/**
 * @param string $ext_name Main class name (eg ImageIO as opposed to ImageIOTheme or ImageIOTest)
 * @return bool
 */
function ext_is_live(string $ext_name): bool {
	if (class_exists($ext_name)) {
		/** @var Extension $ext */
		$ext = new $ext_name();
		return $ext->is_live();
	}
	return false;
}


/** @private */
global $_shm_event_count;
$_shm_event_count = 0;

/**
 * Send an event to all registered Extensions.
 *
 * @param Event $event
 */
function send_event(Event $event) {
	global $_shm_event_listeners, $_shm_event_count, $_shm_ctx;
	if(!isset($_shm_event_listeners[get_class($event)])) return;
	$method_name = "on".str_replace("Event", "", get_class($event));

	// send_event() is performance sensitive, and with the number
	// of times context gets called the time starts to add up
	$ctx_enabled = constant('CONTEXT');

	if($ctx_enabled) $_shm_ctx->log_start(get_class($event));
	// SHIT: http://bugs.php.net/bug.php?id=35106
	$my_event_listeners = $_shm_event_listeners[get_class($event)];
	ksort($my_event_listeners);
	foreach($my_event_listeners as $listener) {
		if($ctx_enabled) $_shm_ctx->log_start(get_class($listener));
		if(method_exists($listener, $method_name)) {
			$listener->$method_name($event);
		}
		if($ctx_enabled) $_shm_ctx->log_endok();
	}
	$_shm_event_count++;
	if($ctx_enabled) $_shm_ctx->log_endok();
}

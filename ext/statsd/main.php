<?php
/*
* Name: StatsD Interface
* Author: Shish <webmaster@shishnet.org>
* License: GPLv2
* Visibility: admin
* Description: Sends Shimmie stats to a StatsD server
* Documentation:
*  define('STATSD_HOST', 'my.server.com:8125'); in shimmie.conf.php to set the host
*/

_d("STATSD_HOST", null);

function dstat($name, $val) {
	StatsDInterface::$stats["shimmie.$name"] = $val;
}

class StatsDInterface extends Extension {
	public static $stats = array();

	private function _stats($type) {
		global $_shm_event_count, $database, $_shm_load_start;
		$time = microtime(true) - $_shm_load_start;
		StatsDInterface::$stats["shimmie.$type.hits"] = "1|c";
		StatsDInterface::$stats["shimmie.$type.time"] = "$time|ms";
		StatsDInterface::$stats["shimmie.$type.time-db"] = "{$database->dbtime}|ms";
		StatsDInterface::$stats["shimmie.$type.memory"] = memory_get_peak_usage(true)."|c";
		StatsDInterface::$stats["shimmie.$type.files"] = count(get_included_files())."|c";
		StatsDInterface::$stats["shimmie.$type.queries"] = $database->query_count."|c";
		StatsDInterface::$stats["shimmie.$type.events"] = $_shm_event_count."|c";
		StatsDInterface::$stats["shimmie.$type.cache-hits"] = $database->cache->get_hits()."|c";
		StatsDInterface::$stats["shimmie.$type.cache-misses"] = $database->cache->get_misses()."|c";
	}

	public function onPageRequest($event) {
		$this->_stats("overall");

		if($event->page_matches("post/view")) {  # 40%
			$this->_stats("post-view");
		}
		else if($event->page_matches("post/list")) {  # 30%
			$this->_stats("post-list");
		}
		else if($event->page_matches("user")) {
			$this->_stats("user");
		}
		else if($event->page_matches("upload")) {
			$this->_stats("upload");
		}
		else if($event->page_matches("rss")) {
			$this->_stats("rss");
		}
		else {
			#global $_shm_load_start;
			#$time = microtime(true) - $_shm_load_start;
			#file_put_contents("data/other.log", "{$_SERVER['REQUEST_URI']} $time\n", FILE_APPEND);
			$this->_stats("other");
		}

		$this->send(StatsDInterface::$stats, 1.0);
		StatsDInterface::$stats = array();
	}

	public function onUserCreation($event) {
		StatsDInterface::$stats["shimmie.events.user_creations"] = "1|c";
	}

	public function onDataUpload($event) {
		StatsDInterface::$stats["shimmie.events.uploads"] = "1|c";
	}

	public function onCommentPosting($event) {
		StatsDInterface::$stats["shimmie.events.comments"] = "1|c";
	}

	public function onImageInfoSet($event) {
		StatsDInterface::$stats["shimmie.events.info-sets"] = "1|c";
	}

	/**
	 * @return int
	 */
	public function get_priority() {return 99;}


    private function send($data, $sampleRate=1) {
        if (!STATSD_HOST) { return; }

        // sampling
        $sampledData = array();

        if ($sampleRate < 1) {
            foreach ($data as $stat => $value) {
                if ((mt_rand() / mt_getrandmax()) <= $sampleRate) {
                    $sampledData[$stat] = "$value|@$sampleRate";
                }
            }
        } else {
            $sampledData = $data;
        }

        if (empty($sampledData)) { return; }

        // Wrap this in a try/catch - failures in any of this should be silently ignored
        try {
            $parts = explode(":", STATSD_HOST);
            $host = $parts[0];
            $port = $parts[1];
            $fp = fsockopen("udp://$host", $port, $errno, $errstr);
            if (! $fp) { return; }
            foreach ($sampledData as $stat => $value) {
                fwrite($fp, "$stat:$value");
            }
            fclose($fp);
        } catch (Exception $e) {
            // ignore any failures.
        }
    }
}

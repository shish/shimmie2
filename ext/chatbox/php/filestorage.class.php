<?

class FileStorage {

	function FileStorage($path, $shoutLog = false) {
		$this->shoutLog = $shoutLog;
		$folder = 'logs';
		if (!is_dir($folder)) $folder = '../' . $folder;
		if (!is_dir($folder)) $folder = '../' . $folder;
	
		$this->path = $folder . '/' . $path . '.txt';
	}
	
	function open($lock = false) {
		$this->handle = fopen($this->path, 'a+');

		if ($lock) {
			$this->lock();
			return $this->load();
		}
	}

	function close(&$array) {
		if (isset($array))
			$this->save($array);
				
		$this->unlock();
		fclose($this->handle);
		unset($this->handle);
	}

	function load() {
		if (($contents = $this->read($this->path)) == null)
			return $this->resetArray();

		return unserialize($contents);
	}

	function save(&$array, $unlock = true) {
		$contents = serialize($array);
		$this->write($contents);
		if ($unlock) $this->unlock();
	}

	function unlock() {
		if (isset($this->handle))
			flock($this->handle, LOCK_UN);
	}
	
	function lock() {
		if (isset($this->handle))
			flock($this->handle, LOCK_EX);
	}

	function read() {
		fseek($this->handle, 0);
		//return stream_get_contents($this->handle);
		return file_get_contents($this->path);

	}

	function write($contents) {
		ftruncate($this->handle, 0);
		fwrite($this->handle, $contents);
	}

	function resetArray() {
		if ($this->shoutLog)
			$default = array(
				'info' => array(
					'latestTimestamp' => -1
				),
	
				'posts' => array()
			);
		else
			$default = array();

		$this->save($default, false);
		return $default;
	}

}

?>
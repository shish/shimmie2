<?php
/*
 * DataUploadEvent:
 *
 * Some data is being uploaded.
 */
class DataUploadEvent extends Event {
	var $user, $tmpname, $metadata, $hash, $type;

	public function DataUploadEvent($user, $tmpname, $metadata) {
		$this->user = $user;
		$this->tmpname = $tmpname;
		
		$this->metadata = $metadata;
		$this->metadata['hash'] = md5_file($tmpname);
		$this->metadata['size'] = filesize($tmpname);
		
		// useful for most file handlers, so pull directly into fields
		$this->hash = $this->metadata['hash'];
		$this->type = strtolower($metadata['extension']);
	}
}
?>

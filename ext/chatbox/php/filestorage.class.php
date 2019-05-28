<?php

class FileStorage
{
    public $shoutLog;
    public $path;
    public $handle;

    public function FileStorage($path, $shoutLog = false)
    {
        $this->shoutLog = $shoutLog;
        $folder = 'logs';
        if (!is_dir($folder)) {
            $folder = '../' . $folder;
        }
        if (!is_dir($folder)) {
            $folder = '../' . $folder;
        }
    
        $this->path = $folder . '/' . $path . '.txt';
    }
    
    public function open($lock = false)
    {
        $this->handle = fopen($this->path, 'a+');

        if ($lock) {
            $this->lock();
            return $this->load();
        }
    }

    public function close(&$array)
    {
        if (isset($array)) {
            $this->save($array);
        }
                
        $this->unlock();
        fclose($this->handle);
        unset($this->handle);
    }

    public function load()
    {
        if (($contents = $this->read($this->path)) == null) {
            return $this->resetArray();
        }

        return unserialize($contents);
    }

    public function save(&$array, $unlock = true)
    {
        $contents = serialize($array);
        $this->write($contents);
        if ($unlock) {
            $this->unlock();
        }
    }

    public function unlock()
    {
        if (isset($this->handle)) {
            flock($this->handle, LOCK_UN);
        }
    }
    
    public function lock()
    {
        if (isset($this->handle)) {
            flock($this->handle, LOCK_EX);
        }
    }

    public function read()
    {
        fseek($this->handle, 0);
        //return stream_get_contents($this->handle);
        return file_get_contents($this->path);
    }

    public function write($contents)
    {
        ftruncate($this->handle, 0);
        fwrite($this->handle, $contents);
    }

    public function resetArray()
    {
        if ($this->shoutLog) {
            $default = [
                'info' => [
                    'latestTimestamp' => -1
                ],
    
                'posts' => []
            ];
        } else {
            $default = [];
        }

        $this->save($default, false);
        return $default;
    }
}

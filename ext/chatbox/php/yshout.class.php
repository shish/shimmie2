<?php

class YShout
{
    public function YShout($path, $admin = false)
    {
        global $storage;
        // Redo to check for folders or just not break, because nonexistent files should be allowed.
        // if (!file_exists($path)) error('That file does not exist.');

        $this->storage = new $storage($path, true);
        $this->admin = $admin;
    }

    public function posts()
    {
        global $null;
        $this->storage->open();
        $s = $this->storage->load();
        $this->storage->close($null);

        if ($s) {
            return $s['posts'];
        }
    }

    public function info()
    {
        global $null;
        $s = $this->storage->open(true);

        $this->storage->close($null);

        if ($s) {
            return $s['info'];
        }
    }

    public function postsAfter($ts)
    {
        $allPosts = $this->posts();

        $posts = [];

        /*	for ($i = sizeof($allPosts) - 1;  $i > -1; $i--) {
                $post = $allPosts[$i];

                if ($post['timestamp'] > $ts)
                    $posts[] = $post;
            } */

        foreach ($allPosts as $post) {
            if ($post['timestamp'] > $ts) {
                $posts[] = $post;
            }
        }

        $this->postProcess($posts);
        return $posts;
    }

    public function latestPosts($num)
    {
        $allPosts = $this->posts();
        $posts = array_slice($allPosts, -$num, $num);

        $this->postProcess($posts);
        return array_values($posts);
    }

    public function hasPostsAfter($ts)
    {
        $info = $this->info();
        $timestamp = $info['latestTimestamp'];
        return $timestamp > $ts;
    }

    public function post($nickname, $message)
    {
        global $prefs;

        if ($this->banned(ip()) /* && !$this->admin*/) {
            return false;
        }

        if (!$this->validate($message, $prefs['messageLength'])) {
            return false;
        }
        if (!$this->validate($nickname, $prefs['nicknameLength'])) {
            return false;
        }

        $message = trim(clean($message));
        $nickname = trim(clean($nickname));
        
        if ($message == '') {
            return false;
        }
        if ($nickname == '') {
            return false;
        }
        
        $timestamp = ts();
        
        $message = $this->censor($message);
        $nickname = $this->censor($nickname);
        
        $post = [
            'nickname' => $nickname,
            'message' => $message,
            'timestamp' => $timestamp,
            'admin' => $this->admin,
            'uid' => md5($timestamp . ' ' . $nickname),
            'adminInfo' => [
                'ip' => ip()
            ]
        ];

        $s = $this->storage->open(true);

        $s['posts'][] = $post;

        if (sizeof($s['posts']) > $prefs['history']) {
            $this->truncate($s['posts']);
        }

        $s['info']['latestTimestamp'] = $post['timestamp'];

        $this->storage->close($s);
        $this->postProcess($post);
        return $post;
    }

    public function truncate(&$array)
    {
        global $prefs;

        $array = array_slice($array, -$prefs['history']);
        $array = array_values($array);
    }

    public function clear()
    {
        global $null;

        $this->storage->open(true);
        $this->storage->resetArray();
        // ? Scared to touch it... Misspelled though. Update: Touched! Used to be $nulls...
        $this->storage->close($null);
    }

    public function bans()
    {
        global $storage, $null;

        $s = new $storage('yshout.bans');
        $s->open();
        $bans = $s->load();
        $s->close($null);

        return $bans;
    }

    public function ban($ip, $nickname = '', $info = '')
    {
        global $storage;

        $s = new $storage('yshout.bans');
        $bans = $s->open(true);

        $bans[] = [
            'ip' => $ip,
            'nickname' => $nickname,
            'info' => $info,
            'timestamp' => ts()
        ];

        $s->close($bans);
    }

    public function banned($ip)
    {
        global $storage, $null;

        $s = new $storage('yshout.bans');
        $bans = $s->open(true);
        $s->close($null);

        foreach ($bans as $ban) {
            if ($ban['ip'] ==  $ip) {
                return true;
            }
        }

        return false;
    }

    public function unban($ip)
    {
        global $storage;

        $s = new $storage('yshout.bans');
        $bans = $s->open(true);

        foreach ($bans as $key=>$value) {
            if ($value['ip'] == $ip) {
                unset($bans[$key]);
            }
        }

        $bans = array_values($bans);
        $s->close($bans);
    }

    public function unbanAll()
    {
        global $storage, $null;

        $s = new $storage('yshout.bans');
        $s->open(true);
        $s->resetArray();
        $s->close($null);
    }

    public function delete($uid)
    {
        global $prefs, $storage;

        
        $s = $this->storage->open(true);

        $posts = $s['posts'];
        
        foreach ($posts as $key=>$value) {
            if (!isset($value['uid'])) {
                unset($posts['key']);
            } elseif ($value['uid'] == $uid) {
                unset($posts[$key]);
            }
        }
        
        $s['posts'] = array_values($posts);
        $this->storage->close($s);

        return true;
    }
    
    public function validate($str, $maxLen)
    {
        return len($str) <= $maxLen;
    }
    
    public function censor($str)
    {
        global $prefs;
        
        $cWords = explode(' ', $prefs['censorWords']);
        $words = explode(' ', $str);
        $endings = '|ed|es|ing|s|er|ers';
        $arrEndings = explode('|', $endings);
        
        foreach ($cWords as $cWord) {
            foreach ($words as $i=>$word) {
                $pattern = '/^(' . $cWord . ')+(' . $endings . ')\W*$/i';
                $words[$i] = preg_replace($pattern, str_repeat('*', strlen($word)), $word);
            }
        }
        
        return implode(' ', $words);
    }

    public function postProcess(&$post)
    {
        if (isset($post['message'])) {
            if ($this->banned($post['adminInfo']['ip'])) {
                $post['banned'] = true;
            }
            if (!$this->admin) {
                unset($post['adminInfo']);
            }
        } else {
            foreach ($post as $key=>$value) {
                if ($this->banned($value['adminInfo']['ip'])) {
                    $post[$key]['banned'] = true;
                }
                if (!$this->admin) {
                    unset($post[$key]['adminInfo']);
                }
            }
        }
    }
}

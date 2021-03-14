<?php declare(strict_types=1);
/**
 * Generic parent class for all events.
 *
 * An event is anything that can be passed around via send_event($blah)
 */
abstract class Event
{
    public bool $stop_processing = false;

    public function __construct()
    {
    }

    public function __toString(): string
    {
        return var_export($this, true);
    }
}


/**
 * A wake-up call for extensions. Upon recieving an InitExtEvent an extension
 * should check that it's database tables are there and install them if not,
 * and set any defaults with Config::set_default_int() and such.
 *
 * This event is sent before $user is set to anything
 */
class InitExtEvent extends Event
{
}


/**
 * A signal that a page has been requested.
 *
 * User requests /view/42 -> an event is generated with $args = array("view",
 * "42"); when an event handler asks $event->page_matches("view"), it returns
 * true and ignores the matched part, such that $event->count_args() = 1 and
 * $event->get_arg(0) = "42"
 */
class PageRequestEvent extends Event
{
    /**
     * @var string[]
     */
    public $args;
    public int $arg_count;
    public int $part_count;

    public function __construct(string $path)
    {
        parent::__construct();
        global $config;

        // trim starting slashes
        $path = ltrim($path, "/");

        // if path is not specified, use the default front page
        if (empty($path)) {   /* empty is faster than strlen */
            $path = $config->get_string(SetupConfig::FRONT_PAGE);
        }

        // break the path into parts
        $args = explode('/', $path);

        $this->args = $args;
        $this->arg_count = count($args);
    }

    /**
     * Test if the requested path matches a given pattern.
     *
     * If it matches, store the remaining path elements in $args
     */
    public function page_matches(string $name): bool
    {
        $parts = explode("/", $name);
        $this->part_count = count($parts);

        if ($this->part_count > $this->arg_count) {
            return false;
        }

        for ($i=0; $i<$this->part_count; $i++) {
            if ($parts[$i] != $this->args[$i]) {
                return false;
            }
        }

        return true;
    }

    /**
     * Get the n th argument of the page request (if it exists.)
     */
    public function get_arg(int $n): string
    {
        $offset = $this->part_count + $n;
        if ($offset >= 0 && $offset < $this->arg_count) {
            return $this->args[$offset];
        } else {
            $nm1 = $this->arg_count - 1;
            throw new SCoreException("Requested an invalid page argument {$offset} / {$nm1}");
        }
    }

    /**
     * If page arg $n is set, then treat that as a 1-indexed page number
     * and return a 0-indexed page number less than $max; else return 0
     */
    public function try_page_num(int $n, ?int $max=null): int
    {
        if ($this->count_args() > $n) {
            $i = $this->get_arg($n);
            if (is_numeric($i) && int_escape($i) > 0) {
                return page_number($i, $max);
            } else {
                return 0;
            }
        } else {
            return 0;
        }
    }

    /**
     * Returns the number of arguments the page request has.
     */
    public function count_args(): int
    {
        return $this->arg_count - $this->part_count;
    }

    /*
     * Many things use these functions
     */

    public function get_search_terms(): array
    {
        $search_terms = [];
        if ($this->count_args() === 2) {
            $search_terms = Tag::explode(Tag::decaret($this->get_arg(0)));
        }
        return $search_terms;
    }

    public function get_page_number(): int
    {
        $page_number = 1;
        if ($this->count_args() === 1) {
            $page_number = int_escape($this->get_arg(0));
        } elseif ($this->count_args() === 2) {
            $page_number = int_escape($this->get_arg(1));
        }
        if ($page_number === 0) {
            $page_number = 1;
        } // invalid -> 0
        return $page_number;
    }

    public function get_page_size(): int
    {
        global $config;
        return $config->get_int(IndexConfig::IMAGES);
    }
}


/**
 * Sent when index.php is called from the command line
 */
class CommandEvent extends Event
{
    public string $cmd = "help";

    /**
     * @var string[]
     */
    public array $args = [];

    /**
     * #param string[] $args
     */
    public function __construct(array $args)
    {
        parent::__construct();
        global $user;

        $opts = [];
        $log_level = SCORE_LOG_WARNING;
        $arg_count = count($args);

        for ($i=1; $i<$arg_count; $i++) {
            switch ($args[$i]) {
                case '-u':
                    $user = User::by_name($args[++$i]);
                    if (is_null($user)) {
                        die("Unknown user");
                    } else {
                        send_event(new UserLoginEvent($user));
                    }
                    break;
                case '-q':
                    $log_level += 10;
                    break;
                case '-v':
                    $log_level -= 10;
                    break;
                default:
                    $opts[] = $args[$i];
                    break;
            }
        }

        if (!defined("CLI_LOG_LEVEL")) {
            define("CLI_LOG_LEVEL", $log_level);
        }

        if (count($opts) > 0) {
            $this->cmd = $opts[0];
            $this->args = array_slice($opts, 1);
        } else {
            print "\n";
            print "Usage: php {$args[0]} [flags] [command]\n";
            print "\n";
            print "Flags:\n";
            print "\t-u [username]\n";
            print "\t\tLog in as the specified user\n";
            print "\t-q / -v\n";
            print "\t\tBe quieter / more verbose\n";
            print "\t\tScale is debug - info - warning - error - critical\n";
            print "\t\tDefault is to show warnings and above\n";
            print "\n";
            print "Currently known commands:\n";
        }
    }
}


/**
 * A signal that some text needs formatting, the event carries
 * both the text and the result
 */
class TextFormattingEvent extends Event
{
    /**
     * For reference
     */
    public string $original;

    /**
     * with formatting applied
     */
    public string $formatted;

    /**
     * with formatting removed
     */
    public string $stripped;

    public function __construct(string $text)
    {
        parent::__construct();
        // We need to escape before formatting, instead of at display time,
        // because formatters will add their own HTML tags into the mix and
        // we don't want to escape those.
        $h_text = html_escape(trim($text));
        $this->original  = $h_text;
        $this->formatted = $h_text;
        $this->stripped  = $h_text;
    }
}


/**
 * A signal that something needs logging
 */
class LogEvent extends Event
{
    /**
     * a category, normally the extension name
     */
    public string $section;

    /**
     * See python...
     */
    public int $priority = 0;

    /**
     * Free text to be logged
     */
    public string $message;

    /**
     * The time that the event was created
     */
    public int $time;

    /**
     * Extra data to be held separate
     *
     * @var string[]
     */
    public array $args;

    public function __construct(string $section, int $priority, string $message)
    {
        parent::__construct();
        $this->section = $section;
        $this->priority = $priority;
        $this->message = $message;
        $this->time = time();
    }
}

class DatabaseUpgradeEvent extends Event
{
}

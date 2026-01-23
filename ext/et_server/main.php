<?php

declare(strict_types=1);

namespace Shimmie2;

use function MicroHTML\emptyHTML;
use function MicroHTML\{PRE};
use function MicroHTML\{TABLE, TD, TH, TR};

use Symfony\Component\Yaml\Yaml;

final class ETServer extends Extension
{
    public const KEY = "et_server";
    public const VERSION_KEY = "et_server_version";

    #[EventListener]
    public function onPageRequest(PageRequestEvent $event): void
    {
        global $database;
        $page = Ctx::$page;

        if ($event->page_matches("register.php", method: "POST", authed: false)) {
            $reports = $event->POST->req("data");
            $database->execute("INSERT INTO registration(data) VALUES(:data)", ["data" => $reports]);
            $page->flash("Your data has been recorded!");
            $page->set_redirect(make_link("et/stats"));
        }

        if ($event->page_matches("et/stats", method: "GET")) {
            $details = $event->GET->get("details") && Ctx::$user->can(ETServerPermission::VIEW_REGISTRATIONS);

            $page->set_title("Statistics");
            $raw = $database->get_col(
                "SELECT data
                FROM registration
                WHERE responded > :datetime",
                ["datetime" => date('Y-m-d H:i:s', time() - (86400 * 365 * 1))]
            );
            $reports = array_filter(array_map(fn ($item) => $this->try_parse($item), $raw), fn ($row) => $row !== null);

            $page->add_block(new Block(
                null,
                emptyHTML(
                    "Aggregate stats for sites who have decided to share their data within
                    the last year. This data is used to decide which PHP versions / databases
                    / web servers / etc to support."
                ),
                position: 0
            ));

            $versions = [];
            foreach ($reports as $r) {
                $v = $r['versions']['shimmie'] ?? $r['Shimmie'];
                if (empty($v)) {
                    $versions["Not Reported"]++;
                } elseif (\Safe\preg_match('/(2\.[0-9]+(\.[0-9]+)?(-[abr][a-z0-9]+)?).*/', $v, $matches)) {
                    $versions[$matches[1]]++;
                } else {
                    $versions[$details ? "($v)" : "Unknown"]++;
                }

            }
            $this->add_table("Shimmie Versions", $versions, 10);

            $versions = [];
            foreach ($reports as $r) {
                $v = $r['versions']['php'] ?? $r["PHP"];
                if (empty($v)) {
                    $versions["Not Reported"]++;
                } elseif (\Safe\preg_match('/^([0-9]+\.[0-9]+).*/', $v, $matches)) {
                    $versions[$matches[1]]++;
                } else {
                    $versions[$details ? "($v)" : "Unknown"]++;
                }
            }
            $this->add_table("PHP Versions", $versions, 20);

            $versions = [];
            foreach ($reports as $r) {
                $v = $r['versions']['db'] ?? $r["Database"];
                if (empty($v)) {
                    $versions["Not Reported"]++;
                } elseif (\Safe\preg_match('#sqlite ([0-9]+\.[0-9]+)[0-9].*#', $v, $matches)) {
                    $versions["SQLite " . $matches[1] . "X"]++;
                } elseif (\Safe\preg_match('#mysql /? ?([0-9]+).*#', $v, $matches)) {
                    $versions[(str_contains($v, "MariaDB") ? "MariaDB" : "MySQL") . " " . $matches[1]]++;
                } elseif (\Safe\preg_match('#pgsql PostgreSQL ([0-9]+).*#', $v, $matches)) {
                    $versions["PostgreSQL " . $matches[1]]++;
                } else {
                    $versions[$details ? "($v)" : "Unknown"]++;
                }
            }
            $this->add_table("Database Versions", $versions, 30);

            $versions = [];
            foreach ($reports as $r) {
                $v = $r['versions']['os'] ?? $r["OS"];
                if (empty($v)) {
                    $versions["Not Reported"]++;
                } elseif (\Safe\preg_match('#Linux [^ ]+ ([0-9]+)#', $v, $matches)) {
                    $versions["Linux " . $matches[1]]++;
                } elseif (\Safe\preg_match('#FreeBSD [^ ]+ ([0-9]+)#', $v, $matches)) {
                    $versions["FreeBSD " . $matches[1]]++;
                } elseif (\Safe\preg_match('#Windows NT [^ ]+ ([0-9]+)#', $v, $matches)) {
                    $versions["Windows NT " . $matches[1]]++;
                } elseif (\Safe\preg_match('#Darwin [^ ]+ ([0-9]+)#', $v, $matches)) {
                    $versions["Darwin " . $matches[1]]++;
                } else {
                    $versions[$details ? "($v)" : "Unknown"]++;
                }
            }
            $this->add_table("Operating System Versions", $versions, 40);

            $versions = [];
            foreach ($reports as $r) {
                $v = $r['versions']['server'] ?? $r["Server"];
                if (empty($v)) {
                    $versions["Not Reported"]++;
                } elseif (\Safe\preg_match('#Apache/([0-9]+(\.[0-9]+)?).*#', $v, $matches)) {
                    $versions["Apache " . $matches[1]]++;
                } elseif (\Safe\preg_match('#Apache#', $v, $matches)) {
                    $versions["Apache"]++;
                } elseif (\Safe\preg_match('#nginx/([0-9]+\.[0-9]+).*#', $v, $matches)) {
                    $versions["Nginx " . $matches[1]]++;
                } elseif (\Safe\preg_match('#lighttpd/([0-9]+\.[0-9]+).*#', $v, $matches)) {
                    $versions["Lighttpd " . $matches[1]]++;
                } elseif (\Safe\preg_match('#Unit/([0-9]+\.[0-9]+).*#', $v, $matches)) {
                    $versions["Unit " . $matches[1]]++;
                } elseif (\Safe\preg_match('#Microsoft-IIS/([0-9]+\.[0-9]+).*#', $v, $matches)) {
                    $versions["IIS " . $matches[1]]++;
                } elseif (\Safe\preg_match('#Caddy/([0-9]+\.[0-9]+).*#', $v, $matches)) {
                    $versions["Caddy " . $matches[1]]++;
                } elseif (\Safe\preg_match('#LiteSpeed#', $v, $matches)) {
                    $versions["LiteSpeed"]++;
                } elseif (\Safe\preg_match('#PHP[ /]([0-9]+\.[0-9]+).*#', $v, $matches)) {
                    $versions["PHP " . $matches[1]]++;
                } else {
                    $versions[$details ? "($v)" : "Unknown"]++;
                }
            }
            $this->add_table("Server Versions", $versions, 50);


        }

        if ($event->page_matches("et/registrations", method: "GET", permission: ETServerPermission::VIEW_REGISTRATIONS)) {
            $page->set_title("Registrations");
            $n = 0;
            foreach ($database->get_all("SELECT responded, data FROM registration ORDER BY responded DESC") as $row) {
                $page->add_block(new Block(
                    $row["responded"],
                    PRE(["style" => "text-align: left; overflow: scroll;"], $row["data"]),
                    "main",
                    $n++
                ));
            }
        }
    }

    /**
     * @param array<string, int> $data
     */
    protected function add_table(string $title, array $data, int $pos): void
    {
        krsort($data);
        $table = TABLE(["class" => "zebra et-stats"]);
        foreach ($data as $key => $value) {
            $table->appendChild(TR(TH($key), TD($value), TD(str_repeat("#", $value))));
        }
        Ctx::$page->add_block(new Block($title, $table, position: $pos));
    }

    /**
     * @return array<mixed>|null
     */
    public function try_parse(string $item): ?array
    {
        try {
            $data = Yaml::parse($item);
            if (!is_array($data)) {
                return null;
            }
            return $data;
        } catch (\Exception $e) {
            return null;
        }
    }

    #[EventListener]
    public function onDatabaseUpgrade(DatabaseUpgradeEvent $event): void
    {
        global $database;

        // shortcut to latest
        if ($this->get_version() < 1) {
            $database->create_table("registration", "
				id SCORE_AIPK,
				responded TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
				data TEXT NOT NULL,
			");
            $this->set_version(1);
        }
    }
}

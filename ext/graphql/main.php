<?php

declare(strict_types=1);

namespace Shimmie2;

use GraphQL\GraphQL as GQL;
use GraphQL\Server\StandardServer;
use GraphQL\Error\DebugFlag;
use GraphQL\Utils\SchemaPrinter;
use Psr\Container\ContainerInterface;
use TheCodingMachine\GraphQLite\Schema;
use TheCodingMachine\GraphQLite\SchemaFactory;

class C implements ContainerInterface
{
    public function get(string $id): mixed
    {
        echo("get($id)\n");
        return null;
    }
    public function has(string $id): bool
    {
        echo("has($id)\n");
        return false;
    }
}

class GraphQL extends Extension
{
    public static function get_schema(): Schema
    {
        global $cache, $_tracer;

        $_tracer->begin("Create Schema");
        $container = new C();
        $factory = new SchemaFactory($cache, $container);
        $factory->addControllerNamespace('Shimmie2\\')
                ->addTypeNamespace('Shimmie2\\');
        $factory->devMode(); // vs prodMode
        $schema = $factory->createSchema();
        $_tracer->end(null);
        return $schema;
    }

    private function cors(): void
    {
        global $config;
        $pat = $config->get_string("graphql_cors_pattern");

        if ($pat && isset($_SERVER['HTTP_ORIGIN'])) {
            if (preg_match("#$pat#", $_SERVER['HTTP_ORIGIN'])) {
                header("Access-Control-Allow-Origin: {$_SERVER['HTTP_ORIGIN']}");
                header('Access-Control-Allow-Credentials: true');
                header('Access-Control-Max-Age: 86400');
            }
        }

        if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
            if (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_METHOD'])) {
                header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
            }
            if (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS'])) {
                header("Access-Control-Allow-Headers: {$_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS']}");
            }
            exit(0);
        }
    }

    public function onInitExt(InitExtEvent $event)
    {
        global $config;
        $config->set_default_string('graphql_cors_pattern', "");
        $config->set_default_bool('graphql_debug', false);
    }

    public function onPageRequest(PageRequestEvent $event)
    {
        global $config, $page;
        if ($event->page_matches("graphql")) {
            $this->cors();
            $t1 = ftime();
            $server = new StandardServer([
                'schema' => $this->get_schema(),
            ]);
            $t2 = ftime();
            $resp = $server->executeRequest();
            if ($config->get_bool("graphql_debug")) {
                $debug = DebugFlag::INCLUDE_DEBUG_MESSAGE | DebugFlag::RETHROW_INTERNAL_EXCEPTIONS;
                $body = $resp->toArray($debug);
            } else {
                $body = $resp->toArray();
            }
            $t3 = ftime();
            $body['stats'] = get_debug_info_arr();
            $body['stats']['graphql_schema_time'] = round($t2 - $t1, 2);
            $body['stats']['graphql_execute_time'] = round($t3 - $t2, 2);
            // sleep(1);
            $page->set_mode(PageMode::DATA);
            $page->set_mime("application/json");
            $page->set_data(\json_encode($body, JSON_UNESCAPED_UNICODE));
        }
        if ($event->page_matches("graphql_upload")) {
            $this->cors();
            $page->set_mode(PageMode::DATA);
            $page->set_mime("application/json");
            $page->set_data(\json_encode(self::handle_uploads()));
        }
    }

    private static function handle_uploads(): array
    {
        global $user;

        if (!$user->can(Permissions::CREATE_IMAGE)) {
            return ["error" => "User cannot create posts"];
        }

        $common_tags = $_POST['common_tags'];
        $common_source = $_POST['common_source'];

        $results = [];
        for ($n = 0; $n < 100; $n++) {
            if (empty($_POST["url$n"]) && empty($_FILES["data$n"])) {
                break;
            }
            if (isset($_FILES["data$n"]) && ($_FILES["data$n"]["size"] == 0 || $_FILES["data$n"]["error"] == UPLOAD_ERR_NO_FILE)) {
                break;
            }
            try {
                $results[] = self::handle_upload($n, $common_tags, $common_source);
            } catch(\Exception $e) {
                $results[] = ["error" => $e->getMessage()];
            }
        }
        return ["results" => $results];
    }

    private static function handle_upload(int $n, string $common_tags, string $common_source): array
    {
        if (!empty($_POST["url$n"])) {
            return ["error" => "URLs not handled yet"];
            $tmpname = "...";
            $filename = "...";
        } else {
            $ec = $_FILES["data$n"]["error"];
            switch($ec) {
                case UPLOAD_ERR_OK:
                    $tmpname = $_FILES["data$n"]["tmp_name"];
                    $filename = $_FILES["data$n"]["name"];
                    break;
                case UPLOAD_ERR_INI_SIZE:
                    return ["error" => "File larger than PHP can handle"];
                default:
                    return ["error" => "Mystery error: $ec"];
            }
        }

        $tags = trim($common_tags . " " . $_POST["tags$n"]);
        $source = $common_source;
        if (!empty($_POST["source$n"])) {
            $source = $_POST["source$n"];
        }
        $event = send_event(new DataUploadEvent($tmpname, [
            'filename' => $filename,
            'tags' => Tag::explode($tags),
            'source' => $source,
        ]));

        return ["image_ids" => array_map(fn ($im) => $im->id, $event->images)];
    }

    public function onCommand(CommandEvent $event)
    {
        if ($event->cmd == "help") {
            print "\tgraphql <query string>\n";
            print "\t\teg 'graphql \"{ post(id: 18) { id, hash } }\"'\n\n";
            print "\tgraphql-schema\n";
            print "\t\tdump the schema\n\n";
        }
        if ($event->cmd == "graphql") {
            $t1 = ftime();
            $schema = $this->get_schema();
            $t2 = ftime();
            $debug = DebugFlag::INCLUDE_DEBUG_MESSAGE | DebugFlag::RETHROW_INTERNAL_EXCEPTIONS;
            $body = GQL::executeQuery($schema, $event->args[0])->toArray($debug);
            $t3 = ftime();
            $body['stats'] = get_debug_info_arr();
            $body['stats']['graphql_schema_time'] = round($t2 - $t1, 2);
            $body['stats']['graphql_execute_time'] = round($t3 - $t2, 2);
            echo \json_encode($body, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        }
        if ($event->cmd == "graphql-schema") {
            $schema = $this->get_schema();
            echo(SchemaPrinter::doPrint($schema));
        }
    }
}

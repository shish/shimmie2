<?php

declare(strict_types=1);

namespace Shimmie2;

use GraphQL\GraphQL as GQL;
use GraphQL\Server\StandardServer;
use GraphQL\Error\DebugFlag;
use GraphQL\Type\Schema;
use GraphQL\Utils\SchemaPrinter;

class GraphQL extends Extension
{
    public static function get_schema(): Schema
    {
        global $_tracer;
        $_tracer->begin("Create Schema");
        $schema = new \GQLA\Schema();
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
            $page->set_mode(PageMode::DATA);
            $page->set_mime("application/json");
            $page->set_data(\json_encode($body, JSON_UNESCAPED_UNICODE));
        }
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
            echo \json_encode($body, JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT);
        }
        if ($event->cmd == "graphql-schema") {
            $schema = $this->get_schema();
            echo(SchemaPrinter::doPrint($schema));
        }
    }
}

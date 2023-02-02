<?php

declare(strict_types=1);

namespace Shimmie2;

use GraphQL\GraphQL as GQL;
use GraphQL\Server\StandardServer;
use GraphQL\Error\DebugFlag;
use GraphQL\Utils\SchemaPrinter;

class GraphQL extends Extension
{
    public function onPageRequest(PageRequestEvent $event)
    {
        global $page;
        if ($event->page_matches("graphql")) {
            $t1 = ftime();
            $server = new StandardServer([
                'schema' => \GQLA\genSchema(),
            ]);
            $t2 = ftime();
            $resp = $server->executeRequest();
            $body = $resp->toArray();
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
            print "\t\teg 'graphql \"{ post_by_id(id: 18) { id, hash } }\"'\n\n";
            print "\tgraphql-schema\n";
            print "\t\tdump the schema\n\n";
        }
        if ($event->cmd == "graphql") {
            $t1 = ftime();
            $schema = \GQLA\genSchema();
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
            $schema = \GQLA\genSchema();
            echo(SchemaPrinter::doPrint($schema));
        }
    }
}

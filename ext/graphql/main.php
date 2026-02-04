<?php

declare(strict_types=1);

namespace Shimmie2;

use GraphQL\Error\DebugFlag;
use GraphQL\GraphQL as GQL;
use GraphQL\Server\StandardServer;
use GraphQL\Type\Schema;
use GraphQL\Utils\SchemaPrinter;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\{InputArgument, InputInterface};
use Symfony\Component\Console\Output\OutputInterface;

#[\GQLA\InputObjectType]
final class MetadataInput
{
    public function __construct(
        #[\GQLA\Field]
        public string $key,
        #[\GQLA\Field]
        public string $value,
    ) {
    }

    /**
     * @param array<MetadataInput> $metadata
     */
    #[\GQLA\Mutation(args: ["post_id" => "Int!", "metadata" => "[MetadataInput!]!"])]
    public static function update_post_metadata(int $post_id, array $metadata): Image
    {
        $image = Image::by_id_ex($post_id);
        $pairs = new QueryArray([]);
        foreach ($metadata as $m) {
            $pairs[$m->key] = $m->value;
        }
        send_event(new ImageInfoSetEvent($image, 0, $pairs));
        return Image::by_id_ex($post_id);
    }
}

function shmFieldResolver(
    mixed $objectValue,
    mixed $args,
    mixed $context,
    \GraphQL\Type\Definition\ResolveInfo $info
): mixed {
    $fieldName = $info->fieldName;
    $property = null;

    if (is_array($objectValue)) {
        if (isset($objectValue[$fieldName])) {
            $property = $objectValue[$fieldName];
        }
    } elseif ($objectValue instanceof \ArrayAccess) {
        if (isset($objectValue->{$fieldName})) {
            $property = $objectValue->{$fieldName};
        } elseif (isset($objectValue[$fieldName])) {
            $property = $objectValue[$fieldName];
        }

    } elseif (is_object($objectValue)) {
        if (isset($objectValue->{$fieldName})) {
            $property = $objectValue->{$fieldName};
        }
    }

    return $property instanceof \Closure
        ? $property($objectValue, $args, $context, $info)
        : $property;
}

final class GraphQL extends Extension
{
    public const KEY = "graphql";

    public static function get_schema(): Schema
    {
        $sSchema = Ctx::$tracer->startSpan("Create Schema");
        $schema = new \GQLA\Schema();
        $sSchema->end();
        return $schema;
    }

    private function cors(): void
    {
        $pat = Ctx::$config->get(GraphQLConfig::CORS_PATTERN);

        if ($pat && isset($_SERVER['HTTP_ORIGIN'])) {
            if (\Safe\preg_match("#$pat#", $_SERVER['HTTP_ORIGIN'])) {
                header("Access-Control-Allow-Origin: {$_SERVER['HTTP_ORIGIN']}");
                header('Access-Control-Allow-Credentials: true');
                header('Access-Control-Max-Age: 86400');
            }
        }

        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            if (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_METHOD'])) {
                header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
            }
            if (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS'])) {
                header("Access-Control-Allow-Headers: {$_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS']}");
            }
            exit(0);
        }
    }

    #[EventListener]
    public function onPageRequest(PageRequestEvent $event): void
    {
        if ($event->page_matches("graphql")) {
            $this->cors();
            $t1 = ftime();
            $server = new StandardServer([
                'schema' => self::get_schema(),
                'fieldResolver' => "\Shimmie2\shmFieldResolver",
            ]);
            $t2 = ftime();
            $resp = $server->executeRequest();
            assert(!is_array($resp));
            assert(is_a($resp, \GraphQL\Executor\ExecutionResult::class));
            if (Ctx::$config->get(GraphQLConfig::DEBUG)) {
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
            Ctx::$page->set_data(MimeType::JSON, \Safe\json_encode($body, JSON_UNESCAPED_UNICODE));
        }
        if ($event->page_matches("graphql_upload")) {
            $this->cors();
            Ctx::$page->set_data(MimeType::JSON, \Safe\json_encode(self::handle_uploads($event->POST)));
        }
    }

    /**
     * @return array{error?:string,results?:array<array{error?:string,image_ids?:int[]}>}
     */
    private static function handle_uploads(QueryArray $post): array
    {
        if (!Ctx::$user->can(ImagePermission::CREATE_IMAGE)) {
            return ["error" => "User cannot create posts"];
        }

        $results = [];
        for ($n = 0; $n < 100; $n++) {
            if (empty($post["url$n"]) && empty($_FILES["data$n"])) {
                break;
            }
            if (isset($_FILES["data$n"]) && ($_FILES["data$n"]["size"] === 0 || $_FILES["data$n"]["error"] === UPLOAD_ERR_NO_FILE)) {
                break;
            }
            try {
                $results[] = ["image_ids" => self::handle_upload($n, $post)];
            } catch (\Exception $e) {
                $results[] = ["error" => $e->getMessage()];
            }
        }
        return ["results" => $results];
    }

    /**
     * @return int[]
     */
    private static function handle_upload(int $n, QueryArray $post): array
    {
        if (!empty($post["url$n"])) {
            throw new UploadException("URLs not handled yet");
        } else {
            $ec = $_FILES["data$n"]["error"];
            switch ($ec) {
                case UPLOAD_ERR_OK:
                    $tmpname = new Path($_FILES["data$n"]["tmp_name"]);
                    $filename = $_FILES["data$n"]["name"];
                    break;
                case UPLOAD_ERR_INI_SIZE:
                    throw new UploadException("File larger than PHP can handle");
                default:
                    throw new UploadException("Mystery error: ".var_export($ec, true));
            }
        }

        $event = Ctx::$database->with_savepoint(function () use ($tmpname, $filename, $n, $post) {
            return send_event(new DataUploadEvent($tmpname, $filename, $n, $post));
        });

        return array_map(fn ($im) => $im->id, $event->images);
    }

    #[EventListener]
    public function onCliGen(CliGenEvent $event): void
    {
        $event->app->register('graphql:query')
            ->addArgument('query', InputArgument::REQUIRED)
            ->setDescription('Run a GraphQL query')
            ->setCode(function (InputInterface $input, OutputInterface $output): int {
                $query = $input->getArgument('query');
                $t1 = ftime();
                $schema = self::get_schema();
                $t2 = ftime();
                $debug = DebugFlag::INCLUDE_DEBUG_MESSAGE | DebugFlag::RETHROW_INTERNAL_EXCEPTIONS;
                $body = GQL::executeQuery($schema, $query, fieldResolver: "\Shimmie2\shmFieldResolver")->toArray($debug);
                $t3 = ftime();
                $body['stats'] = get_debug_info_arr();
                $body['stats']['graphql_schema_time'] = round($t2 - $t1, 2);
                $body['stats']['graphql_execute_time'] = round($t3 - $t2, 2);
                echo \Safe\json_encode($body, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
                return Command::SUCCESS;
            });
        $event->app->register('graphql:schema')
            ->setDescription('Print out the GraphQL schema')
            ->setCode(function (InputInterface $input, OutputInterface $output): int {
                $schema = self::get_schema();
                echo(SchemaPrinter::doPrint($schema));
                return Command::SUCCESS;
            });
    }
}

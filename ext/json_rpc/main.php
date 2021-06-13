<?php
declare(strict_types=1);

class ApiRequestEvent extends Event
{
    public $method;  // string
    public $params;  // array|object
    public $id;  // int|string
    public $result;  // any

    public function __construct(string $method, $params, $id)
    {
        parent::__construct();
        $this->method = $method;
        $this->params = $params;
        $this->id = $id;
        $this->result = null;
    }

    public static function from_array(array $req)
    {
        $method = $req["method"];
        $params = $req["params"] ?? [];
        $id = $req["id"];
        return new ApiRequestEvent($method, $params, $id);
    }
}

class JsonRpc extends Extension
{
    public function onPageRequest(PageRequestEvent $event)
    {
        global $page;
        if ($event->page_matches("api/json")) {
            $page->set_mode(PageMode::DATA);
            $in = json_decode(file_get_contents('php://input'), true);

            // $in is a request
            if (array_key_exists("jsonrpc", $in)) {
                $out = $this->get_response($in);
                if (!is_null($out)) {
                    $page->set_data(json_encode($out));
                }
            }
            // assume $in is a list of requests
            else {
                $out = [];
                foreach ($in as $req) {
                    $res = $this->get_response($req);
                    if (!is_null($res)) {
                        $out[] = $res;
                    }
                }
                $page->set_data(json_encode($out));
            }
        }
    }

    public function onCommand(CommandEvent $event)
    {
        if ($event->cmd == "help") {
            print "\tjson-rpc <method> <params>\n";
            print "\t\teg 'json-rpc get-posts'\n\n";
        }
        if ($event->cmd == "json-rpc") {
            print(json_encode($this->get_response([
                "id" => 1,
                "method" => $event->args[0],
                "params" => json_decode($event->args[1] ?? "[]"),
            ]), JSON_PRETTY_PRINT) . "\n");
        }
    }

    private function get_response(array $req): ?array
    {
        $evt = ApiRequestEvent::from_array($req);
        try {
            send_event($evt);
            if (is_null($evt->id)) {
                return null;
            }
            return [
                "jsonrpc" => "2.0",
                "result" => $evt->result,
                "id" => $evt->id,
            ];
        } catch (Throwable $e) {
            return [
                "jsonrpc" => "2.0",
                "error" => [
                    "code" => -1,
                    "message" => $e->getMessage(),
                    "data" => null,
                ],
                "id" => $evt->id,
            ];
        }
    }

    public function onApiRequest(ApiRequestEvent $event)
    {
        if ($event->method == "echo") {
            $event->result = $event->params;
        }
    }
}

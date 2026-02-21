<?php

declare(strict_types=1);

namespace Shimmie2;

function parse_http_signature($header)
{
    // FIXME: this isn't a proper parser, there might be comma IN the fields...
    list($keyId, $headers, $claimed_signature) = explode(",", $header);
}

function fetch_key($keyId)
{
}

class APUser
{
    public function by_id($id)
    {
        $data = urlopen($id);
        $js = json_decode($data);
        $this->js = $js;
        $this->publicKey = $js['publicKey']['publicKeyPem'];
    }
}

class ActivityPub extends Extension
{
    public function onPageRequest(PageRequestEvent $event)
    {
        global $page, $user;

        if ($event->page_matches(".well-known/webfinger")) {
            $page->set_mode(PageMode::DATA);
            $req = $_GET['resource'];
            if (preg_match("/acct:(.*)@(.*)/", $req, $matches)) {
                $duser = User::by_name($matches[1]);
                $page->set_data(json_encode(
                    [
                        "subject" => "acct:{$duser->name}@{$matches[2]}",

                        "links" => [
                            [
                                "rel" => "self",
                                "type" => "application/activity+json",
                                "href" => make_http(make_link("activitypub/user/{$duser->name}"))
                            ]
                        ]
                    ],
                    JSON_PRETTY_PRINT
                ));
            }
        }

        if ($event->page_matches("activitypub/user")) {
            $duser = User::by_name($event->get_arg(0));
            $id = make_http(make_link("activitypub/user/{$duser->name}"));
            $pubkey = file_get_contents("ext/activitypub/example_public.pem");
            $privkey = file_get_contents("ext/activitypub/example_private.pem");

            $page->set_mode(PageMode::DATA);
            $page->set_data(json_encode([
                "@context" => [
                    "https://www.w3.org/ns/activitystreams",
                    "https://w3id.org/security/v1"
                ],

                "id" => $id,
                "type" => "Person",
                "preferredUsername" => $duser->name,
                "inbox" => make_http(make_link("activitypub/inbox")),

                "publicKey" => [
                    "id" => "$id#main-key",
                    "owner" => $id,
                    "publicKeyPem" => $pubkey
                ]
            ], JSON_PRETTY_PRINT));
        }

        if ($event->page_matches("activitypub/inbox")) {
            list($keyId, $headers, $claimed_signature) = \ActivityPub\parse_http_signature($_SERVER['HTTP_SIGNATURE']);
            $pubkey = \ActivityPub\fetch_key($keyId);
            $target = make_link("activitypub/inbox");
            $headers_to_sign = "(request-target): post $target\n";
            foreach (explode(" ", $headers) as $header) {
                $headers_to_sign .= "$header: " . $_SERVER["HTTP_" . strtoupper($header)];
            }
            $real_signature = sign($headers_to_sign);
            // TODO: check date header
        }


        /*
        create comment

        POST to parent '/inbox' with header:

        Signature: keyId="https://my-example.com/actor#main-key",headers="(request-target) host date",signature="..."

        headers to be hashed & signed:

            (request-target): post /inbox
            host: mastodon.social
            date: Sun, 06 Nov 1994 08:49:37 GMT

require 'http'
require 'openssl'

document      = File.read('create-hello-world.json')
date          = Time.now.utc.httpdate
keypair       = OpenSSL::PKey::RSA.new(File.read('private.pem'))
signed_string = "(request-target): post /inbox\nhost: mastodon.social\ndate: #{date}"
signature     = Base64.strict_encode64(keypair.sign(OpenSSL::Digest::SHA256.new, signed_string))
header        = 'keyId="https://my-example.com/actor",headers="(request-target) host date",signature="' + signature + '"'

HTTP.headers({ 'Host': 'mastodon.social', 'Date': date, 'Signature': header })
    .post('https://mastodon.social/inbox', body: document)



        {
    "@context": "https://www.w3.org/ns/activitystreams",

    "id": "https://my-example.com/create-hello-world",
    "type": "Create",
    "actor": "https://my-example.com/actor",

    "object": {
        "id": "https://my-example.com/hello-world",
        "type": "Note",
        "published": "2018-06-23T17:17:11Z",
        "attributedTo": "https://my-example.com/actor",
        "inReplyTo": "https://mastodon.social/@Gargron/100254678717223630",
        "content": "<p>Hello world</p>",
        "to": "https://www.w3.org/ns/activitystreams#Public"
    }
}
        */
    }
}

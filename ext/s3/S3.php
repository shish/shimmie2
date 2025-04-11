<?php

// https://github.com/ericnorris/amazon-s3-php

declare(strict_types=1);

namespace S3Client;

/*
The MIT License (MIT)

Copyright (c) 2014 Eric Norris

Permission is hereby granted, free of charge, to any person obtaining a copy
of this software and associated documentation files (the "Software"), to deal
in the Software without restriction, including without limitation the rights
to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
copies of the Software, and to permit persons to whom the Software is
furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in
all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
THE SOFTWARE.
*/

class S3
{
    private string $access_key;
    private string $secret_key;
    private string $endpoint;
    private \CurlMultiHandle $multi_curl;
    /** @var array<int,mixed> */
    private array $curl_opts;

    public function __construct(string $access_key, string $secret_key, string $endpoint = 's3.amazonaws.com')
    {
        $this->access_key = $access_key;
        $this->secret_key = $secret_key;
        $this->endpoint = $endpoint;

        $this->multi_curl = curl_multi_init();

        $this->curl_opts = [
            CURLOPT_CONNECTTIMEOUT => 30,
            CURLOPT_LOW_SPEED_LIMIT => 1,
            CURLOPT_LOW_SPEED_TIME => 30
        ];
    }

    public function __destruct()
    {
        curl_multi_close($this->multi_curl);
    }

    /** @param array<int,mixed> $curl_opts */
    public function useCurlOpts(array $curl_opts): S3
    {
        $this->curl_opts = $curl_opts;
        return $this;
    }

    /** @param array<string,string> $headers */
    public function putObject(string $bucket, string $path, string $file, array $headers = []): S3Response
    {
        $uri = "$bucket/$path";

        $request = (new S3Request('PUT', $this->endpoint, $uri))
            ->setFileContents($file)
            ->setHeaders($headers)
            ->useMultiCurl($this->multi_curl)
            ->useCurlOpts($this->curl_opts)
            ->sign($this->access_key, $this->secret_key);

        return $request->getResponse();
    }

    /** @param array<string,string> $headers */
    public function getObjectInfo(string $bucket, string $path, array $headers = []): S3Response
    {
        $uri = "$bucket/$path";

        $request = (new S3Request('HEAD', $this->endpoint, $uri))
            ->setHeaders($headers)
            ->useMultiCurl($this->multi_curl)
            ->useCurlOpts($this->curl_opts)
            ->sign($this->access_key, $this->secret_key);

        return $request->getResponse();
    }

    /**
     * @param array<string,string> $headers
     */
    public function getObject(
        string $bucket,
        string $path,
        array $headers = []
    ): S3Response {
        $uri = "$bucket/$path";

        $request = (new S3Request('GET', $this->endpoint, $uri))
            ->setHeaders($headers)
            ->useMultiCurl($this->multi_curl)
            ->useCurlOpts($this->curl_opts)
            ->sign($this->access_key, $this->secret_key);

        return $request->getResponse();
    }

    /** @param array<string,string> $headers */
    public function deleteObject(string $bucket, string $path, array $headers = []): S3Response
    {
        $uri = "$bucket/$path";

        $request = (new S3Request('DELETE', $this->endpoint, $uri))
            ->setHeaders($headers)
            ->useMultiCurl($this->multi_curl)
            ->useCurlOpts($this->curl_opts)
            ->sign($this->access_key, $this->secret_key);

        return $request->getResponse();
    }

    /** @param array<string,string> $headers */
    public function getBucket(string $bucket, array $headers = []): S3Response
    {
        $request = (new S3Request('GET', $this->endpoint, $bucket))
            ->setHeaders($headers)
            ->useMultiCurl($this->multi_curl)
            ->useCurlOpts($this->curl_opts)
            ->sign($this->access_key, $this->secret_key);

        $response = $request->getResponse();
        /*
        if (!isset($response->error)) {
            $body = simplexml_load_string($response->body);

            if ($body) {
                $response->body = $body;
            }
        }
        */
        return $response;
    }

}

class S3Request
{
    /** @var array<string, string> */
    private array $headers;
    private \CurlHandle $curl;
    private S3Response $response;
    private ?\CurlMultiHandle $multi_curl;

    public function __construct(
        private string $action,
        private string $endpoint,
        private string $uri
    ) {
        $this->headers = [
            'Content-MD5' => '',
            'Content-Type' => '',
            'Date' => gmdate('D, d M Y H:i:s T'),
            'Host' => $this->endpoint
        ];

        $this->curl = curl_init();
        $this->response = new S3Response();

        $this->multi_curl = null;
    }

    /**
     * @param resource|string $file The file to send
     */
    public function setFileContents($file): S3Request
    {
        if (is_resource($file)) {
            $hash_ctx = hash_init('md5');
            $length = hash_update_stream($hash_ctx, $file);
            $md5 = hash_final($hash_ctx, true);

            rewind($file);

            curl_setopt($this->curl, CURLOPT_PUT, true);
            curl_setopt($this->curl, CURLOPT_INFILE, $file);
            curl_setopt($this->curl, CURLOPT_INFILESIZE, $length);
        } elseif (is_string($file)) {
            curl_setopt($this->curl, CURLOPT_POSTFIELDS, $file);
            $md5 = md5($file, true);
        } else {
            throw new \InvalidArgumentException('Invalid file type');
        }

        $this->headers['Content-MD5'] = base64_encode($md5);

        return $this;
    }

    /**
     * @param array<string,string> $custom_headers
     */
    public function setHeaders(array $custom_headers): S3Request
    {
        $this->headers = array_merge($this->headers, $custom_headers);
        return $this;
    }

    public function sign(string $access_key, string $secret_key): S3Request
    {
        $canonical_amz_headers = $this->getCanonicalAmzHeaders();

        $string_to_sign = '';
        $string_to_sign .= "{$this->action}\n";
        $string_to_sign .= "{$this->headers['Content-MD5']}\n";
        $string_to_sign .= "{$this->headers['Content-Type']}\n";
        $string_to_sign .= "{$this->headers['Date']}\n";

        if (count($canonical_amz_headers) > 0) {
            $string_to_sign .= implode("\n", $canonical_amz_headers) . "\n";
        }

        $string_to_sign .= "/{$this->uri}";

        $signature = base64_encode(
            hash_hmac('sha1', $string_to_sign, $secret_key, true)
        );

        $this->headers['Authorization'] = "AWS $access_key:$signature";

        return $this;
    }

    public function useMultiCurl(\CurlMultiHandle $mh): S3Request
    {
        $this->multi_curl = $mh;
        return $this;
    }

    /**
     * @param array<int,mixed> $curl_opts
     */
    public function useCurlOpts(array $curl_opts): S3Request
    {
        curl_setopt_array($this->curl, $curl_opts);

        return $this;
    }

    public function getResponse(): S3Response
    {
        $http_headers = array_map(
            function ($header, $value) {
                return "$header: $value";
            },
            array_keys($this->headers),
            array_values($this->headers)
        );

        curl_setopt_array($this->curl, [
            CURLOPT_USERAGENT => 'ericnorris/amazon-s3-php',
            CURLOPT_URL => "https://{$this->endpoint}/{$this->uri}",
            CURLOPT_HTTPHEADER => $http_headers,
            CURLOPT_HEADER => false,
            CURLOPT_RETURNTRANSFER => false,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_WRITEFUNCTION => [
                $this->response, '__curlWriteFunction'
            ],
            CURLOPT_HEADERFUNCTION => [
                $this->response, '__curlHeaderFunction'
            ]
        ]);

        switch ($this->action) {
            case 'DELETE':
                curl_setopt($this->curl, CURLOPT_CUSTOMREQUEST, 'DELETE');
                break;
            case 'HEAD':
                curl_setopt($this->curl, CURLOPT_NOBODY, true);
                break;
            case 'POST':
                curl_setopt($this->curl, CURLOPT_CUSTOMREQUEST, 'POST');
                break;
            case 'PUT':
                curl_setopt($this->curl, CURLOPT_CUSTOMREQUEST, 'PUT');
                break;
        }

        if (isset($this->multi_curl)) {
            curl_multi_add_handle($this->multi_curl, $this->curl);

            $running = null;
            do {
                curl_multi_exec($this->multi_curl, $running);
                curl_multi_select($this->multi_curl);
            } while ($running > 0);

            curl_multi_remove_handle($this->multi_curl, $this->curl);
        } else {
            $success = curl_exec($this->curl);
        }

        $this->response->finalize($this->curl);

        curl_close($this->curl);

        return $this->response;
    }

    /**
     * @return array<string,string>
     */
    private function getCanonicalAmzHeaders(): array
    {
        $canonical_amz_headers = [];

        foreach ($this->headers as $header => $value) {
            $header = trim(strtolower($header));
            $value = trim($value);

            if (strpos($header, 'x-amz-') === 0) {
                $canonical_amz_headers[$header] = "$header:$value";
            }
        }

        ksort($canonical_amz_headers);

        return $canonical_amz_headers;
    }

}

class S3Response
{
    /** @var array{"code":int|string, "message":string, "resource"?:string}|null */
    public ?array $error;
    public ?int $code;
    /** @var array<string,string> */
    public array $headers;
    public string $body;

    public function __construct()
    {
        $this->error = null;
        $this->code = null;
        $this->headers = [];
        $this->body = "";
    }

    public function __curlWriteFunction(\CurlHandle $ch, string $data): int
    {
        $this->body .= $data;
        return strlen($data);
    }

    public function __curlHeaderFunction(\CurlHandle $ch, string $data): int
    {
        $header = explode(':', $data);

        if (count($header) === 2) {
            list($key, $value) = $header;
            $this->headers[$key] = trim($value);
        }

        return strlen($data);
    }

    public function finalize(\CurlHandle $ch): void
    {
        if (curl_errno($ch) || curl_error($ch)) {
            $this->error = [
                'code' => curl_errno($ch),
                'message' => curl_error($ch),
            ];
        } else {
            $this->code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $content_type = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);

            if ($this->code > 300 && $content_type === 'application/xml') {
                $response = simplexml_load_string($this->body);

                if ($response) {
                    $error = [
                        'code' => (string)$response->Code,
                        'message' => (string)$response->Message,
                    ];

                    if (isset($response->Resource)) {
                        $error['resource'] = (string)$response->Resource;
                    }

                    $this->error = $error;
                }
            }
        }
    }
}

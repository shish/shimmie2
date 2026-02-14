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

class S3Exception extends \Exception
{
    /** @param array<string, mixed> $context */
    public function __construct(
        string $message,
        int $code = 0,
        private array $context = [],
        ?\Throwable $previous = null
    ) {
        parent::__construct($message, $code, $previous);
    }

    /** @return array<string, mixed> */
    public function getContext(): array
    {
        return $this->context;
    }
}

class S3
{
    private string $access_key;
    private string $secret_key;
    private string $endpoint;
    private string $region;

    public function __construct(
        string $access_key,
        string $secret_key,
        string $endpoint = 's3.amazonaws.com',
        string $region = 'auto',
    ) {
        // if endpoint doesn't start with http:// or https://, prepend https://
        // this allows users to specify endpoints like "s3.amazonaws.com"
        // without needing to include the protocol
        if (!preg_match('/^https?:\/\//', $endpoint)) {
            $endpoint = 'https://' . $endpoint;
        }

        $this->access_key = $access_key;
        $this->secret_key = $secret_key;
        $this->endpoint = $endpoint;
        $this->region = $region;
    }

    /** @param array<string,string> $headers */
    public function putObject(string $bucket, string $path, string $file, array $headers = []): S3Response
    {
        $uri = "$bucket/$path";

        $request = (new S3Request('PUT', $this->endpoint, $uri, $this->region))
            ->setFileContents($file)
            ->setHeaders($headers)
            ->sign($this->access_key, $this->secret_key);

        return $request->getResponse();
    }

    /** @param array<string,string> $headers */
    public function getObjectInfo(string $bucket, string $path, array $headers = []): S3Response
    {
        $uri = "$bucket/$path";

        $request = (new S3Request('HEAD', $this->endpoint, $uri, $this->region))
            ->setHeaders($headers)
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

        $request = (new S3Request('GET', $this->endpoint, $uri, $this->region))
            ->setHeaders($headers)
            ->sign($this->access_key, $this->secret_key);

        return $request->getResponse();
    }

    /** @param array<string,string> $headers */
    public function deleteObject(string $bucket, string $path, array $headers = []): S3Response
    {
        $uri = "$bucket/$path";

        $request = (new S3Request('DELETE', $this->endpoint, $uri, $this->region))
            ->setHeaders($headers)
            ->sign($this->access_key, $this->secret_key);

        return $request->getResponse();
    }

    /** @param array<string,string> $headers */
    public function getBucket(string $bucket, array $headers = []): S3Response
    {
        $request = (new S3Request('GET', $this->endpoint, $bucket, $this->region))
            ->setHeaders($headers)
            ->sign($this->access_key, $this->secret_key);

        return $request->getResponse();
    }
}

class S3Request
{
    /** @var array<string, string> */
    private array $headers;
    private \CurlHandle $curl;
    private string $body_content = '';

    public function __construct(
        private string $action,
        private string $endpoint,
        private string $uri,
        private string $region = 'auto',
    ) {
        $host = parse_url($this->endpoint, PHP_URL_HOST);
        if (!is_string($host)) {
            throw new \InvalidArgumentException("Invalid endpoint URL: $endpoint");
        }

        $this->headers = [
            'Host' => $host
        ];

        $this->curl = curl_init();
    }

    /**
     * @param string $file The file to send
     */
    public function setFileContents(string $file): S3Request
    {
        $this->body_content = $file;
        curl_setopt($this->curl, CURLOPT_POSTFIELDS, $file);
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
        // AWS Signature Version 4
        $timestamp = gmdate('Ymd\THis\Z');
        $date = gmdate('Ymd');

        $this->headers['x-amz-date'] = $timestamp;
        $this->headers['x-amz-content-sha256'] = hash('sha256', $this->body_content);

        // Step 1: Create canonical request
        $canonical_uri = '/' . $this->uri;
        $canonical_querystring = '';
        $canonical_headers = '';
        $signed_headers = '';

        // Sort headers and build canonical headers
        ksort($this->headers);
        $header_names = [];
        foreach ($this->headers as $key => $value) {
            $key_lower = strtolower($key);
            $header_names[] = $key_lower;
            $canonical_headers .= $key_lower . ':' . trim($value) . "\n";
        }
        $signed_headers = implode(';', $header_names);

        $payload_hash = $this->headers['x-amz-content-sha256'];

        $canonical_request = $this->action . "\n" .
            $canonical_uri . "\n" .
            $canonical_querystring . "\n" .
            $canonical_headers . "\n" .
            $signed_headers . "\n" .
            $payload_hash;

        // Step 2: Create string to sign
        $algorithm = 'AWS4-HMAC-SHA256';
        $credential_scope = $date . '/' . $this->region . '/s3/aws4_request';
        $string_to_sign = $algorithm . "\n" .
            $timestamp . "\n" .
            $credential_scope . "\n" .
            hash('sha256', $canonical_request);

        // Step 3: Calculate signature
        $k_date = hash_hmac('sha256', $date, 'AWS4' . $secret_key, true);
        $k_region = hash_hmac('sha256', $this->region, $k_date, true);
        $k_service = hash_hmac('sha256', 's3', $k_region, true);
        $k_signing = hash_hmac('sha256', 'aws4_request', $k_service, true);
        $signature = hash_hmac('sha256', $string_to_sign, $k_signing);

        // Step 4: Add authorization header
        $this->headers['Authorization'] = $algorithm . ' ' .
            'Credential=' . $access_key . '/' . $credential_scope . ', ' .
            'SignedHeaders=' . $signed_headers . ', ' .
            'Signature=' . $signature;

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
            CURLOPT_USERAGENT => 'shish/shimmie2',
            CURLOPT_URL => "{$this->endpoint}/{$this->uri}",
            CURLOPT_HTTPHEADER => $http_headers,
            CURLOPT_HEADER => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
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

        $response_raw = curl_exec($this->curl);

        if ($response_raw === false) {
            $error_msg = curl_error($this->curl);
            $error_code = curl_errno($this->curl);
            curl_close($this->curl);
            throw new S3Exception("cURL error: $error_msg", $error_code);
        }
        if ($response_raw === true) {
            // This can happen with HEAD requests where CURLOPT_NOBODY is true
            $response_raw = '';
        }

        $header_size = curl_getinfo($this->curl, CURLINFO_HEADER_SIZE);
        $http_code = curl_getinfo($this->curl, CURLINFO_HTTP_CODE);
        curl_close($this->curl);

        $header_text = substr($response_raw, 0, $header_size);
        $body = substr($response_raw, $header_size);

        // Parse headers
        $headers = [];
        $header_lines = explode("\r\n", $header_text);
        foreach ($header_lines as $line) {
            $parts = explode(':', $line, 2);
            if (count($parts) === 2) {
                $headers[trim($parts[0])] = trim($parts[1]);
            }
        }

        // Check for HTTP errors
        if ($http_code >= 300) {
            $content_type = $headers['Content-Type'] ?? '';
            $error_message = "S3 request failed with HTTP code $http_code";
            $context = ['http_code' => $http_code, 'headers' => $headers];

            if (strpos($content_type, 'application/xml') !== false && $body) {
                $xml = simplexml_load_string($body);
                if ($xml) {
                    $error_code = (string)($xml->Code ?? 'Unknown');
                    $error_message = (string)($xml->Message ?? $error_message);
                    $context['s3_error_code'] = $error_code;
                    if (isset($xml->Resource)) {
                        $context['resource'] = (string)$xml->Resource;
                    }
                }
            }

            throw new S3Exception($error_message, $http_code, $context);
        }

        return new S3Response($http_code, $headers, $body);
    }
}

class S3Response
{
    public function __construct(
        public int $code,
        /** @var array<string,string> */
        public array $headers,
        public string $body,
    ) {
    }
}

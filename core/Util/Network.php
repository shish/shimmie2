<?php

declare(strict_types=1);

namespace Shimmie2;

class Network
{
    public static function is_trusted_proxy(): bool
    {
        $ra = $_SERVER['REMOTE_ADDR'] ?? "0.0.0.0";
        foreach (SysConfig::getTrustedProxies() as $proxy) {
            if ($ra === $proxy) { // check for "unix:" before checking IPs
                return true;
            }
            if (Network::ip_in_range($ra, $proxy)) {
                return true;
            }
        }
        return false;
    }

    public static function is_bot(): bool
    {
        $ua = $_SERVER["HTTP_USER_AGENT"] ?? "No UA";
        return (
            str_contains($ua, "Googlebot")
            || str_contains($ua, "YandexBot")
            || str_contains($ua, "bingbot")
            || str_contains($ua, "msnbot")
            || str_contains($ua, "PetalBot")
        );
    }

    /**
     * Get real IP if behind a reverse proxy
     */
    public static function get_real_ip(): string
    {
        $ip = $_SERVER['REMOTE_ADDR'];

        if ($ip == "unix:") {
            $ip = "0.0.0.0";
        }

        if (Network::is_trusted_proxy()) {
            if (isset($_SERVER['HTTP_X_REAL_IP'])) {
                if (filter_var($ip, FILTER_VALIDATE_IP)) {
                    $ip = $_SERVER['HTTP_X_REAL_IP'];
                }
            }

            if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
                $ips = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
                $last_ip = $ips[count($ips) - 1];
                if (filter_var($last_ip, FILTER_VALIDATE_IP)) {
                    $ip = $last_ip;
                }
            }
        }

        return $ip;
    }

    /**
     * Get the currently active IP, masked to make it not change when the last
     * octet or two change, for use in session cookies and such
     */
    public static function get_session_ip(Config $config): string
    {
        $mask = $config->get_string(UserAccountsConfig::SESSION_HASH_MASK);
        // even if the database says "null", the default setting should take effect
        assert($mask !== null);
        $addr = Network::get_real_ip();
        try {
            $addr = \Safe\inet_ntop(\Safe\inet_pton($addr) & \Safe\inet_pton($mask));
        } catch (\Safe\Exceptions\NetworkException $e) {
            throw new ServerError("Failed to mask IP address ($addr/$mask)");
        }
        return $addr;
    }

    /**
     * @param non-empty-string $url
     * @return array<string, string|string[]>
     */
    public static function fetch_url(string $url, string $mfile): array
    {
        global $config;

        if ($config->get_string(UploadConfig::TRANSLOAD_ENGINE) === "curl" && function_exists("curl_init")) {
            $ch = curl_init($url);
            assert($ch !== false);
            $fp = \Safe\fopen($mfile, "w");

            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            # curl_setopt($ch, CURLOPT_VERBOSE, true);
            curl_setopt($ch, CURLOPT_HEADER, true);
            curl_setopt($ch, CURLOPT_REFERER, $url);
            curl_setopt($ch, CURLOPT_USERAGENT, "Shimmie-".SysConfig::getVersion());
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

            $response = curl_exec($ch);
            if ($response === false) {
                throw new FetchException("cURL failed: ".curl_error($ch));
            }
            if ($response === true) { // we use CURLOPT_RETURNTRANSFER, so this should never happen
                throw new FetchException("cURL failed successfully??");
            }

            $header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
            $header_text = trim(substr($response, 0, $header_size));
            $headers = Network::http_parse_headers(implode("\n", \Safe\preg_split('/\R/', $header_text)));
            $body = substr($response, $header_size);

            curl_close($ch);
            fwrite($fp, $body);
            fclose($fp);
        } elseif ($config->get_string(UploadConfig::TRANSLOAD_ENGINE) === "wget") {
            $s_url = escapeshellarg($url);
            $s_mfile = escapeshellarg($mfile);
            system("wget --no-check-certificate $s_url --output-document=$s_mfile");
            if (!file_exists($mfile)) {
                throw new FetchException("wget failed");
            }
            $headers = [];
        } elseif ($config->get_string(UploadConfig::TRANSLOAD_ENGINE) === "fopen") {
            $fp_in = @fopen($url, "r");
            $fp_out = fopen($mfile, "w");
            if (!$fp_in || !$fp_out) {
                throw new FetchException("fopen failed");
            }
            $length = 0;
            while (!feof($fp_in) && $length <= $config->get_int(UploadConfig::SIZE)) {
                $data = \Safe\fread($fp_in, 8192);
                $length += strlen($data);
                fwrite($fp_out, $data);
            }
            fclose($fp_in);
            fclose($fp_out);

            $headers = Network::http_parse_headers(implode("\n", $http_response_header));
        } else {
            throw new FetchException("No transload engine configured");
        }

        if (filesize($mfile) == 0) {
            @unlink($mfile);
            throw new FetchException("No data found in $url -- perhaps the site has hotlink protection?");
        }

        return $headers;
    }

    /**
     * Figure out if an IP is in a specified range
     *
     * from https://uk.php.net/network
     */
    public static function ip_in_range(string $IP, string $CIDR): bool
    {
        $parts = explode("/", $CIDR);
        if (count($parts) == 1) {
            $parts[1] = "32";
        }
        list($net, $mask) = $parts;

        $ip_net = ip2long($net);
        $ip_mask = ~((1 << (32 - (int)$mask)) - 1);

        $ip_ip = ip2long($IP);

        $ip_ip_net = $ip_ip & $ip_mask;

        return ($ip_ip_net == $ip_net);
    }

    /**
     * @return array<string, string|string[]>
     */
    public static function http_parse_headers(string $raw_headers): array
    {
        $headers = [];

        foreach (explode("\n", $raw_headers) as $h) {
            $h = explode(':', $h, 2);

            if (isset($h[1])) {
                if (!isset($headers[$h[0]])) {
                    $headers[$h[0]] = trim($h[1]);
                } elseif (is_array($headers[$h[0]])) {
                    $tmp = array_merge($headers[$h[0]], [trim($h[1])]);
                    $headers[$h[0]] = $tmp;
                } else {
                    $tmp = array_merge([$headers[$h[0]]], [trim($h[1])]);
                    $headers[$h[0]] = $tmp;
                }
            }
        }
        return $headers;
    }

    /**
     * HTTP Headers can sometimes be lowercase which will cause issues.
     * In cases like these, we need to make sure to check for them if the camelcase version does not exist.
     *
     * @param array<string, mixed> $headers
     */
    public static function find_header(array $headers, string $name): ?string
    {
        $header = null;

        if (array_key_exists($name, $headers)) {
            $header = $headers[$name];
        } else {
            $headers = array_change_key_case($headers); // convert all to lower case.
            $lc_name = strtolower($name);

            if (array_key_exists($lc_name, $headers)) {
                $header = $headers[$lc_name];
            }
        }

        return $header;
    }
}

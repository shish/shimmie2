<?php

declare(strict_types=1);

namespace Shimmie2;

final class Network
{
    public static function is_trusted_proxy(): bool
    {
        $ra = $_SERVER['REMOTE_ADDR'] ?? "0.0.0.0";
        if ($ra === "unix:") {
            return true;
        }

        $ra = IPAddress::parse($ra);
        foreach (SysConfig::getTrustedProxies() as $proxy) {
            if (IPRange::parse($proxy)->contains($ra)) {
                return true;
            }
        }
        return false;
    }

    public static function is_bot(): bool
    {
        /** @var string $ua */
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
    public static function get_real_ip(): IPAddress
    {
        $ip = $_SERVER['REMOTE_ADDR'];

        if ($ip === "unix:") {
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

        return IPAddress::parse($ip);
    }

    /**
     * Get the currently active IP, masked to make it not change when the last
     * octet or two change, for use in session cookies and such
     */
    public static function get_session_ip(): IPAddress
    {
        $mask = Ctx::$config->get(UserAccountsConfig::SESSION_HASH_MASK);
        $addr = (string)Network::get_real_ip();
        try {
            $addr = \Safe\inet_ntop(\Safe\inet_pton($addr) & \Safe\inet_pton($mask));
        } catch (\Safe\Exceptions\NetworkException $e) {
            throw new ServerError("Failed to mask IP address ($addr/$mask)");
        }
        return IPAddress::parse($addr);
    }

    /**
     * @param non-empty-string $url
     * @return header-array
     */
    public static function fetch_url(string $url, Path $mfile): array
    {
        if (Ctx::$config->get(UploadConfig::TRANSLOAD_ENGINE) === "curl" && function_exists("curl_init")) {
            $ch = curl_init($url);
            assert($ch !== false);
            $fp = \Safe\fopen($mfile->str(), "w");

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
        } elseif (Ctx::$config->get(UploadConfig::TRANSLOAD_ENGINE) === "wget") {
            $s_url = escapeshellarg($url);
            $s_mfile = escapeshellarg($mfile->str());
            system("wget --no-check-certificate $s_url --output-document=$s_mfile");
            if (!$mfile->exists()) {
                throw new FetchException("wget failed");
            }
            $headers = [];
        } elseif (Ctx::$config->get(UploadConfig::TRANSLOAD_ENGINE) === "fopen") {
            $fp_in = @fopen($url, "r");
            $fp_out = fopen($mfile->str(), "w");
            if (!$fp_in || !$fp_out) {
                throw new FetchException("fopen failed");
            }
            $length = 0;
            while (!feof($fp_in) && $length <= Ctx::$config->get(UploadConfig::SIZE)) {
                $data = \Safe\fread($fp_in, 8192);
                $length += strlen($data);
                fwrite($fp_out, $data);
            }
            fclose($fp_in);
            fclose($fp_out);

            $headers = Network::http_parse_headers(implode("\n", http_get_last_response_headers() ?? []));
        } else {
            throw new FetchException("No transload engine configured");
        }

        if ($mfile->filesize() === 0) {
            @$mfile->unlink();
            throw new FetchException("No data found in $url -- perhaps the site has hotlink protection?");
        }

        return $headers;
    }

    /**
     * @return header-array
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
     * @param header-array $headers
     * @return string|array<string>|null
     */
    public static function find_header(array $headers, string $name): mixed
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

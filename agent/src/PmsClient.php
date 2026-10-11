<?php

namespace Code4Each\UpworkAgent;

/**
 * HTTPS client for the PMS agent API. Retries network errors and 5xx with
 * exponential backoff; 4xx (e.g. 409 run already active, 423 agent disabled)
 * are returned to the caller unretried.
 */
class PmsClient
{
    /** @var string */
    private $baseUrl;
    /** @var string */
    private $token;
    /** @var Logger */
    private $log;
    /** @var int */
    private $retries;
    /** @var callable */
    private $sleep;

    public function __construct(string $baseUrl, string $token, Logger $log, int $retries = 3, ?callable $sleep = null)
    {
        $this->baseUrl = rtrim($baseUrl, '/');
        $this->token = $token;
        $this->log = $log;
        $this->retries = $retries;
        $this->sleep = $sleep ?? function (int $seconds) { sleep($seconds); };
    }

    /** @return array{status: int, body: array|null} */
    public function get(string $path): array
    {
        return $this->request('GET', $path);
    }

    /** @return array{status: int, body: array|null} */
    public function post(string $path, array $payload = []): array
    {
        return $this->request('POST', $path, $payload);
    }

    private function request(string $method, string $path, ?array $payload = null): array
    {
        $attempt = 0;
        while (true) {
            $attempt++;
            [$status, $raw, $error] = $this->send($method, $path, $payload);

            if ($error === null && $status < 500) {
                return ['status' => $status, 'body' => json_decode((string) $raw, true)];
            }

            $this->log->warning('pms.request_failed', ['method' => $method, 'path' => $path, 'status' => $status, 'error' => $error, 'attempt' => $attempt]);
            if ($attempt > $this->retries) {
                return ['status' => $status ?: 0, 'body' => null];
            }
            ($this->sleep)(min(60, 2 ** $attempt)); // 2s, 4s, 8s ...
        }
    }

    /** @return array{0: int, 1: string|null, 2: string|null} [status, body, curl error] */
    protected function send(string $method, string $path, ?array $payload): array
    {
        $ch = curl_init($this->baseUrl . $path);
        $headers = ['Accept: application/json', 'Authorization: Bearer ' . $this->token];
        $options = [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_TIMEOUT => 60,
        ];
        if ($payload !== null) {
            $headers[] = 'Content-Type: application/json';
            $options[CURLOPT_POSTFIELDS] = json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        }
        $options[CURLOPT_HTTPHEADER] = $headers;
        curl_setopt_array($ch, $options);

        $body = curl_exec($ch);
        $error = $body === false ? curl_error($ch) : null;
        $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        return [$status, $body === false ? null : $body, $error];
    }
}

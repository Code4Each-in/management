<?php

namespace Code4Each\UpworkAgent;

/** Structured JSON-lines log. Secrets are redacted by key name. */
class Logger
{
    const SECRET_KEYS = ['token', 'authorization', 'password', 'secret', 'cookie', 'pms_token'];

    /** @var string */
    private $dir;
    /** @var bool */
    private $echo;

    public function __construct(string $dir, bool $echo = false)
    {
        $this->dir = rtrim($dir, '/');
        $this->echo = $echo;
        if (!is_dir($this->dir)) {
            @mkdir($this->dir, 0750, true);
        }
    }

    public function info(string $event, array $context = []): void { $this->write('info', $event, $context); }
    public function warning(string $event, array $context = []): void { $this->write('warning', $event, $context); }
    public function error(string $event, array $context = []): void { $this->write('error', $event, $context); }

    private function write(string $level, string $event, array $context): void
    {
        $line = json_encode(['ts' => gmdate('c'), 'level' => $level, 'event' => $event] + self::redact($context), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        @file_put_contents($this->dir . '/agent-' . gmdate('Y-m-d') . '.log', $line . "\n", FILE_APPEND | LOCK_EX);
        if ($this->echo) {
            fwrite(STDERR, $line . "\n");
        }
    }

    public static function redact(array $context): array
    {
        foreach ($context as $key => $value) {
            if (is_array($value)) {
                $context[$key] = self::redact($value);
            } elseif (is_string($key) && self::isSecretKey($key)) {
                $context[$key] = '[redacted]';
            } elseif (is_string($value)) {
                // Bearer tokens that slipped into a message.
                $context[$key] = preg_replace('/Bearer\s+[A-Za-z0-9|._-]+/', 'Bearer [redacted]', $value);
            }
        }

        return $context;
    }

    private static function isSecretKey(string $key): bool
    {
        foreach (self::SECRET_KEYS as $secret) {
            if (stripos($key, $secret) !== false) {
                return true;
            }
        }

        return false;
    }
}

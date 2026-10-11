<?php

namespace Code4Each\UpworkAgent;

/**
 * Local worker settings (agent.env). Business configuration - search profiles,
 * scoring, schedule - comes from the PMS, never from here.
 */
class Config
{
    /** @var array */
    private $values;

    public function __construct(array $values)
    {
        $this->values = $values;
    }

    public static function load(string $file): self
    {
        $values = [];
        if (is_file($file)) {
            foreach (file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
                if (preg_match('/^\s*([A-Z0-9_]+)\s*=\s*(.*)\s*$/', $line, $m) && $line[0] !== '#') {
                    $values[$m[1]] = trim($m[2], "\"'");
                }
            }
        }
        foreach ($values as $key => $_) {
            $env = getenv($key);
            if ($env !== false && $env !== '') {
                $values[$key] = $env;
            }
        }

        return new self($values);
    }

    public function get(string $key, $default = null)
    {
        return array_key_exists($key, $this->values) && $this->values[$key] !== '' ? $this->values[$key] : $default;
    }

    public function require(string $key): string
    {
        $value = $this->get($key);
        if ($value === null) {
            throw new \RuntimeException("Missing required setting {$key} in agent.env");
        }

        return (string) $value;
    }

    /** The PMS token: from PMS_TOKEN_COMMAND (e.g. macOS Keychain) or PMS_TOKEN. Never logged. */
    public function pmsToken(): string
    {
        if ($command = $this->get('PMS_TOKEN_COMMAND')) {
            $token = trim((string) shell_exec($command . ' 2>/dev/null'));
            if ($token !== '') {
                return $token;
            }
            throw new \RuntimeException('PMS_TOKEN_COMMAND returned nothing (is the Keychain item present?)');
        }

        return $this->require('PMS_TOKEN');
    }
}

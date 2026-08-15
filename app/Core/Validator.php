<?php

namespace App\Core;

/**
 * Validator
 *
 * Lightweight rule-based input validation.
 * Supported rules: required, string, email, int, min:n, max:n,
 * in:a,b,c, date, domain, ip, boolean.
 */
class Validator
{
    /** @var array<string,array<int,string>> */
    private array $errors = [];

    /** @var array<string,mixed> */
    private array $data;

    /**
     * @param array<string,mixed> $data
     */
    public function __construct(array $data)
    {
        $this->data = $data;
    }

    /**
     * @param array<string,mixed> $data
     * @param array<string,string> $rules  field => "rule1|rule2:arg"
     */
    public static function make(array $data, array $rules): self
    {
        $v = new self($data);
        $v->validate($rules);
        return $v;
    }

    /**
     * @param array<string,string> $rules
     */
    public function validate(array $rules): void
    {
        foreach ($rules as $field => $ruleString) {
            $value = $this->data[$field] ?? null;
            $ruleList = explode('|', $ruleString);

            // Skip optional fields that are absent, unless "required".
            $isRequired = in_array('required', $ruleList, true);
            $isPresent  = $value !== null && $value !== '';

            if (!$isRequired && !$isPresent) {
                continue;
            }

            foreach ($ruleList as $rule) {
                [$name, $arg] = array_pad(explode(':', $rule, 2), 2, null);
                $this->applyRule($field, $value, $name, $arg);
            }
        }
    }

    private function applyRule(string $field, mixed $value, string $rule, ?string $arg): void
    {
        switch ($rule) {
            case 'required':
                if ($value === null || $value === '' || (is_array($value) && $value === [])) {
                    $this->addError($field, "{$field} is required");
                }
                break;
            case 'string':
                if ($value !== null && !is_string($value)) {
                    $this->addError($field, "{$field} must be a string");
                }
                break;
            case 'email':
                if ($value !== null && $value !== '' && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
                    $this->addError($field, "{$field} must be a valid email");
                }
                break;
            case 'int':
                if ($value !== null && $value !== '' && filter_var($value, FILTER_VALIDATE_INT) === false) {
                    $this->addError($field, "{$field} must be an integer");
                }
                break;
            case 'boolean':
                if ($value !== null && !in_array($value, [true, false, 0, 1, '0', '1'], true)) {
                    $this->addError($field, "{$field} must be boolean");
                }
                break;
            case 'min':
                if ($arg !== null && is_string($value) && mb_strlen($value) < (int) $arg) {
                    $this->addError($field, "{$field} must be at least {$arg} characters");
                }
                break;
            case 'max':
                if ($arg !== null && is_string($value) && mb_strlen($value) > (int) $arg) {
                    $this->addError($field, "{$field} must be at most {$arg} characters");
                }
                break;
            case 'in':
                $allowed = $arg !== null ? explode(',', $arg) : [];
                if ($value !== null && $value !== '' && !in_array((string) $value, $allowed, true)) {
                    $this->addError($field, "{$field} is invalid");
                }
                break;
            case 'date':
                if ($value !== null && $value !== '' && strtotime((string) $value) === false) {
                    $this->addError($field, "{$field} must be a valid date");
                }
                break;
            case 'domain':
                if ($value !== null && $value !== '' && !$this->isValidDomain((string) $value)) {
                    $this->addError($field, "{$field} must be a valid domain");
                }
                break;
            case 'ip':
                if ($value !== null && $value !== '' && !filter_var($value, FILTER_VALIDATE_IP)) {
                    $this->addError($field, "{$field} must be a valid IP address");
                }
                break;
        }
    }

    private function isValidDomain(string $domain): bool
    {
        // Accept host names, optionally with scheme/path stripped by caller.
        $domain = preg_replace('#^https?://#i', '', $domain) ?? $domain;
        $domain = explode('/', $domain)[0];
        return (bool) preg_match('/^(?=.{1,253}$)([a-z0-9](-?[a-z0-9])*\.)+[a-z]{2,}$/i', $domain);
    }

    private function addError(string $field, string $message): void
    {
        $this->errors[$field][] = $message;
    }

    public function fails(): bool
    {
        return $this->errors !== [];
    }

    /**
     * @return array<string,array<int,string>>
     */
    public function errors(): array
    {
        return $this->errors;
    }

    public function firstError(): string
    {
        foreach ($this->errors as $messages) {
            return $messages[0] ?? 'Validation failed';
        }
        return 'Validation failed';
    }
}

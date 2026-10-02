<?php
declare(strict_types=1);

namespace App\Infrastructure\Http;

/**
 * Validator class to validate input data with customizable rules.
 * @author: Adevlinux
 */
class Validator
{
    /**
     * Data to validate
     * @var array
     */
    private array $data = [];

    /**
     * Validation rules
     * @var array
     */
    private array $rules = [];

    /**
     * Validation errors
     * @var array
     */
    private array $errors = [];

    /**
     * Custom error messages
     * @var array
     */
    private array $messages = [];

    /**
     * Constructor
     * @param array $data
     * @param array $rules
     * @param array $messages
     */
    public function __construct(array $data = [], array $rules = [], array $messages = [])
    {
        $this->data = $data;
        $this->rules = $rules;
        $this->messages = $messages;
    }

    /**
     * Factory method to create validator instance
     * @param array $data
     * @param array $rules
     * @param array $messages
     * @return self
     */
    public static function make(array $data, array $rules, array $messages = []): self
    {
        return new self($data, $rules, $messages);
    }

    /**
     * Validate the data
     * @return bool
     */
    public function validate(): bool
    {
        $this->errors = [];

        foreach ($this->rules as $field => $ruleString) {
            $rulesArray = explode('|', $ruleString);
            $value = $this->data[$field] ?? null;

            foreach ($rulesArray as $rule) {
                $this->validateRule($field, $value, trim($rule));
            }
        }

        return empty($this->errors);
    }

    /**
     * Validate a single rule
     * @param string $field
     * @param mixed $value
     * @param string $rule
     * @return void
     */
    private function validateRule(string $field, $value, string $rule): void
    {
        if (str_contains($rule, ':')) {
            [$ruleName, $parameter] = explode(':', $rule, 2);
            $ruleName = trim($ruleName);
            $parameter = trim($parameter);
        } else {
            $ruleName = $rule;
            $parameter = null;
        }

        switch ($ruleName) {
            case 'required':
                $this->validateRequired($field, $value);
                break;
            case 'email':
                $this->validateEmail($field, $value);
                break;
            case 'min':
                $this->validateMin($field, $value, $parameter);
                break;
            case 'max':
                $this->validateMax($field, $value, $parameter);
                break;
            case 'between':
                $this->validateBetween($field, $value, $parameter);
                break;
            case 'numeric':
                $this->validateNumeric($field, $value);
                break;
            case 'string':
                $this->validateString($field, $value);
                break;
            case 'integer':
                $this->validateInteger($field, $value);
                break;
            case 'regex':
                $this->validateRegex($field, $value, $parameter);
                break;
            case 'confirmed':
                $this->validateConfirmed($field, $value);
                break;
            case 'unique':
                $this->validateUnique($field, $value, $parameter);
                break;
            case 'in':
                $this->validateIn($field, $value, $parameter);
                break;
        }
    }

    /**
     * Validate required field
     * @param string $field
     * @param mixed $value
     * @return void
     */
    private function validateRequired(string $field, $value): void
    {
        if (empty($value) && $value !== '0') {
            $this->addError($field, 'required', "$field is required");
        }
    }

    /**
     * Validate email
     * @param string $field
     * @param mixed $value
     * @return void
     */
    private function validateEmail(string $field, $value): void
    {
        if (!empty($value) && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
            $this->addError($field, 'email', "$field must be a valid email address");
        }
    }

    /**
     * Validate minimum length
     * @param string $field
     * @param mixed $value
     * @param string $parameter
     * @return void
     */
    private function validateMin(string $field, $value, $parameter): void
    {
        if (!empty($value)) {
            $length = is_numeric($value) ? (int) $value : strlen((string) $value);
            if ($length < (int) $parameter) {
                $this->addError($field, 'min', "$field must be at least $parameter characters long");
            }
        }
    }

    /**
     * Validate maximum length
     * @param string $field
     * @param mixed $value
     * @param string $parameter
     * @return void
     */
    private function validateMax(string $field, $value, $parameter): void
    {
        if (!empty($value)) {
            $length = is_numeric($value) ? (int) $value : strlen((string) $value);
            if ($length > (int) $parameter) {
                $this->addError($field, 'max', "$field must be no more than $parameter characters long");
            }
        }
    }

    /**
     * Validate between min and max
     * @param string $field
     * @param mixed $value
     * @param string $parameter
     * @return void
     */
    private function validateBetween(string $field, $value, $parameter): void
    {
        if (!empty($value)) {
            [$min, $max] = explode(',', $parameter);
            $length = is_numeric($value) ? (int) $value : strlen((string) $value);
            if ($length < (int) $min || $length > (int) $max) {
                $this->addError($field, 'between', "$field must be between $min and $max characters long");
            }
        }
    }

    /**
     * Validate numeric
     * @param string $field
     * @param mixed $value
     * @return void
     */
    private function validateNumeric(string $field, $value): void
    {
        if (!empty($value) && !is_numeric($value)) {
            $this->addError($field, 'numeric', "$field must be numeric");
        }
    }

    /**
     * Validate string
     * @param string $field
     * @param mixed $value
     * @return void
     */
    private function validateString(string $field, $value): void
    {
        if (!empty($value) && !is_string($value)) {
            $this->addError($field, 'string', "$field must be a string");
        }
    }

    /**
     * Validate integer
     * @param string $field
     * @param mixed $value
     * @return void
     */
    private function validateInteger(string $field, $value): void
    {
        if (!empty($value) && !is_int($value) && !ctype_digit((string) $value)) {
            $this->addError($field, 'integer', "$field must be an integer");
        }
    }

    /**
     * Validate with regex pattern
     * @param string $field
     * @param mixed $value
     * @param string $parameter
     * @return void
     */
    private function validateRegex(string $field, $value, $parameter): void
    {
        if (!empty($value) && !preg_match($parameter, (string) $value)) {
            $this->addError($field, 'regex', "$field has an invalid format");
        }
    }

    /**
     * Validate confirmed (field_confirmation must match field)
     * @param string $field
     * @param mixed $value
     * @return void
     */
    private function validateConfirmed(string $field, $value): void
    {
        $confirmField = $field . '_confirmation';
        $confirmValue = $this->data[$confirmField] ?? null;
        
        if ($value !== $confirmValue) {
            $this->addError($field, 'confirmed', "$field does not match the confirmation");
        }
    }

    /**
     * Validate unique in database
     * @param string $field
     * @param mixed $value
     * @param string $parameter Format: 'table,column'
     * @return void
     */
    private function validateUnique(string $field, $value, $parameter): void
    {
        // TODO: Implement database check
        // [$table, $column] = explode(',', $parameter);
    }

    /**
     * Validate value is in array
     * @param string $field
     * @param mixed $value
     * @param string $parameter Format: 'value1,value2,value3'
     * @return void
     */
    private function validateIn(string $field, $value, $parameter): void
    {
        if (!empty($value)) {
            $allowedValues = array_map('trim', explode(',', $parameter));
            if (!in_array((string) $value, $allowedValues)) {
                $this->addError($field, 'in', "$field has an invalid value");
            }
        }
    }

    /**
     * Add error to errors array
     * @param string $field
     * @param string $rule
     * @param string $defaultMessage
     * @return void
     */
    private function addError(string $field, string $rule, string $defaultMessage): void
    {
        $key = "{$field}.{$rule}";
        $message = $this->messages[$key] ?? $defaultMessage;

        if (!isset($this->errors[$field])) {
            $this->errors[$field] = [];
        }

        $this->errors[$field][] = $message;
    }

    /**
     * Get all errors
     * @return array
     */
    public function errors(): array
    {
        return $this->errors;
    }

    /**
     * Get errors for specific field
     * @param string $field
     * @return array
     */
    public function fieldErrors(string $field): array
    {
        return $this->errors[$field] ?? [];
    }

    /**
     * Check if validation failed
     * @return bool
     */
    public function fails(): bool
    {
        return !empty($this->errors);
    }

    /**
     * Get first error message (as string)
     * @return string|null
     */
    public function firstError(): ?string
    {
        if (empty($this->errors)) {
            return null;
        }

        $firstField = array_key_first($this->errors);
        return $this->errors[$firstField][0] ?? null;
    }

    /**
     * Get validated data (only validated fields)
     * @return array
     */
    public function validated(): array
    {
        $validated = [];
        
        foreach ($this->rules as $field => $rules) {
            if (isset($this->data[$field])) {
                $validated[$field] = $this->data[$field];
            }
        }

        return $validated;
    }
}

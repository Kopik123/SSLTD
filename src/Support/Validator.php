<?php

namespace App\Support;

/**
 * Simple Validation Framework
 * 
 * Provides basic validation rules for form data
 */
class Validator
{
    private array $data;
    private array $rules;
    private array $errors = [];
    private array $customMessages = [];

    /**
     * Constructor
     *
     * @param array $data Data to validate
     * @param array $rules Validation rules
     * @param array $customMessages Custom error messages
     */
    public function __construct(array $data, array $rules, array $customMessages = [])
    {
        $this->data = $data;
        $this->rules = $rules;
        $this->customMessages = $customMessages;
    }

    /**
     * Run validation
     *
     * @return bool True if validation passes
     */
    public function validate(): bool
    {
        foreach ($this->rules as $field => $ruleString) {
            $rules = explode('|', $ruleString);
            
            foreach ($rules as $rule) {
                $this->applyRule($field, $rule);
            }
        }
        
        return empty($this->errors);
    }

    /**
     * Get validation errors
     *
     * @return array Validation errors
     */
    public function errors(): array
    {
        return $this->errors;
    }

    /**
     * Check if validation failed
     *
     * @return bool True if there are errors
     */
    public function fails(): bool
    {
        return !empty($this->errors);
    }

    /**
     * Check if validation passed
     *
     * @return bool True if no errors
     */
    public function passes(): bool
    {
        return empty($this->errors);
    }

    /**
     * Apply validation rule to field
     *
     * @param string $field Field name
     * @param string $rule Rule to apply
     * @return void
     */
    private function applyRule(string $field, string $rule): void
    {
        $value = $this->data[$field] ?? null;
        
        // Parse rule parameters (e.g., min:5, max:100)
        $parts = explode(':', $rule, 2);
        $ruleName = $parts[0];
        $parameter = $parts[1] ?? null;
        
        $passed = match ($ruleName) {
            'required' => $this->validateRequired($value),
            'email' => $this->validateEmail($value),
            'min' => $this->validateMin($value, (int)$parameter),
            'max' => $this->validateMax($value, (int)$parameter),
            'numeric' => $this->validateNumeric($value),
            'integer' => $this->validateInteger($value),
            'string' => $this->validateString($value),
            'alpha' => $this->validateAlpha($value),
            'alphanumeric' => $this->validateAlphanumeric($value),
            'url' => $this->validateUrl($value),
            'in' => $this->validateIn($value, $parameter),
            'regex' => $this->validateRegex($value, $parameter),
            default => true
        };
        
        if (!$passed) {
            $this->addError($field, $ruleName, $parameter);
        }
    }

    /**
     * Add validation error
     *
     * @param string $field Field name
     * @param string $rule Rule that failed
     * @param string|null $parameter Rule parameter
     * @return void
     */
    private function addError(string $field, string $rule, ?string $parameter): void
    {
        $key = "$field.$rule";
        
        if (isset($this->customMessages[$key])) {
            $message = $this->customMessages[$key];
        } else {
            $message = $this->getDefaultMessage($field, $rule, $parameter);
        }
        
        $this->errors[$field][] = $message;
    }

    /**
     * Get default error message
     *
     * @param string $field Field name
     * @param string $rule Rule name
     * @param string|null $parameter Rule parameter
     * @return string Error message
     */
    private function getDefaultMessage(string $field, string $rule, ?string $parameter): string
    {
        return match ($rule) {
            'required' => "The $field field is required.",
            'email' => "The $field must be a valid email address.",
            'min' => "The $field must be at least $parameter characters.",
            'max' => "The $field may not be greater than $parameter characters.",
            'numeric' => "The $field must be a number.",
            'integer' => "The $field must be an integer.",
            'string' => "The $field must be a string.",
            'alpha' => "The $field may only contain letters.",
            'alphanumeric' => "The $field may only contain letters and numbers.",
            'url' => "The $field must be a valid URL.",
            'in' => "The selected $field is invalid.",
            'regex' => "The $field format is invalid.",
            default => "The $field is invalid."
        };
    }

    /**
     * Validate required field
     *
     * @param mixed $value Field value
     * @return bool True if valid
     */
    private function validateRequired($value): bool
    {
        if ($value === null || $value === '') {
            return false;
        }
        
        if (is_string($value) && trim($value) === '') {
            return false;
        }
        
        return true;
    }

    /**
     * Validate email address
     *
     * @param mixed $value Field value
     * @return bool True if valid
     */
    private function validateEmail($value): bool
    {
        if ($value === null || $value === '') {
            return true; // Empty is valid (use 'required' for mandatory)
        }
        
        return filter_var($value, FILTER_VALIDATE_EMAIL) !== false;
    }

    /**
     * Validate minimum length
     *
     * @param mixed $value Field value
     * @param int $min Minimum length
     * @return bool True if valid
     */
    private function validateMin($value, int $min): bool
    {
        if ($value === null || $value === '') {
            return true;
        }
        
        if (is_numeric($value)) {
            return $value >= $min;
        }
        
        return mb_strlen((string)$value) >= $min;
    }

    /**
     * Validate maximum length
     *
     * @param mixed $value Field value
     * @param int $max Maximum length
     * @return bool True if valid
     */
    private function validateMax($value, int $max): bool
    {
        if ($value === null || $value === '') {
            return true;
        }
        
        if (is_numeric($value)) {
            return $value <= $max;
        }
        
        return mb_strlen((string)$value) <= $max;
    }

    /**
     * Validate numeric value
     *
     * @param mixed $value Field value
     * @return bool True if valid
     */
    private function validateNumeric($value): bool
    {
        if ($value === null || $value === '') {
            return true;
        }
        
        return is_numeric($value);
    }

    /**
     * Validate integer value
     *
     * @param mixed $value Field value
     * @return bool True if valid
     */
    private function validateInteger($value): bool
    {
        if ($value === null || $value === '') {
            return true;
        }
        
        return filter_var($value, FILTER_VALIDATE_INT) !== false;
    }

    /**
     * Validate string value
     *
     * @param mixed $value Field value
     * @return bool True if valid
     */
    private function validateString($value): bool
    {
        if ($value === null) {
            return true;
        }
        
        return is_string($value);
    }

    /**
     * Validate alphabetic characters only
     *
     * @param mixed $value Field value
     * @return bool True if valid
     */
    private function validateAlpha($value): bool
    {
        if ($value === null || $value === '') {
            return true;
        }
        
        return ctype_alpha((string)$value);
    }

    /**
     * Validate alphanumeric characters only
     *
     * @param mixed $value Field value
     * @return bool True if valid
     */
    private function validateAlphanumeric($value): bool
    {
        if ($value === null || $value === '') {
            return true;
        }
        
        return ctype_alnum((string)$value);
    }

    /**
     * Validate URL
     *
     * @param mixed $value Field value
     * @return bool True if valid
     */
    private function validateUrl($value): bool
    {
        if ($value === null || $value === '') {
            return true;
        }
        
        return filter_var($value, FILTER_VALIDATE_URL) !== false;
    }

    /**
     * Validate value is in list
     *
     * @param mixed $value Field value
     * @param string $list Comma-separated list of allowed values
     * @return bool True if valid
     */
    private function validateIn($value, string $list): bool
    {
        if ($value === null || $value === '') {
            return true;
        }
        
        $allowed = explode(',', $list);
        return in_array($value, $allowed, true);
    }

    /**
     * Validate value matches regex pattern
     *
     * @param mixed $value Field value
     * @param string $pattern Regex pattern
     * @return bool True if valid
     */
    private function validateRegex($value, string $pattern): bool
    {
        if ($value === null || $value === '') {
            return true;
        }
        
        return preg_match($pattern, (string)$value) === 1;
    }

    /**
     * Static helper to validate data
     *
     * @param array $data Data to validate
     * @param array $rules Validation rules
     * @param array $customMessages Custom error messages
     * @return static Validator instance
     */
    public static function make(array $data, array $rules, array $customMessages = []): static
    {
        $validator = new static($data, $rules, $customMessages);
        $validator->validate();
        return $validator;
    }
}

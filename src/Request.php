<?php

namespace Cartrabbit\Request;

use Cartrabbit\Request\Validation\Rules;
use Valitron\Validator;

class Request
{

    public static $validator;

    public $data = [];

    protected $customRuleInstance;

    protected static $sanitizeCallbacks = [
        'text' => 'sanitize_text_field',
        'string' => 'sanitize_text_field',
        'NULL' => 'sanitize_text_field',
        'null' => 'sanitize_text_field',
        'boolean' => 'sanitize_text_field',
        'integer' => 'sanitize_text_field',
        'double' => 'sanitize_text_field',
        'title' => 'sanitize_title',
        'email' => 'sanitize_email',
        'url' => 'sanitize_url',
        'key' => 'sanitize_key',
        'meta' => 'sanitize_meta',
        'option' => 'sanitize_option',
        'file' => 'sanitize_file_name',
        'mime' => 'sanitize_mime_type',
        'class' => 'sanitize_html_class',
        'html' => [__CLASS__, 'sanitizeHtml'],
        'content' => [__CLASS__, 'sanitizeContent'],
        'array' => [__CLASS__, 'sanitizeArray'],
    ];

    public function __construct($data)
    {
        $this->initialize($data);
    }

    protected static $request;


    public static function getSanitizedUserInput($data)
    {
        return static::sanitizeUserData($data, 'array');
    }

    public static function make($data)
    {
        return new self($data);
    }

    public function setCustomRuleInstance($object)
    {
        if ($object instanceof Rules) {
            $this->customRuleInstance = $object;
        } else {
            throw new \Exception("Custom Rule Class must be instance  Rules");
        }

        return $this;

    }

    private function initialize($data)
    {
        $this->data = $data;
    }

    private static function sanitizeUserData($data, $type)
    {
        $type = $type ?: gettype($data);

        if (!in_array($type, array_keys(static::$sanitizeCallbacks))) {
            throw new \UnexpectedValueException('The sanitization Type is Not Present in the request class');
        }

        return call_user_func(static::$sanitizeCallbacks[$type], $data);
    }

    public static function sanitize($value, $type)
    {
        //sanitize directly if neeed
    }

    public function get($key, $default = null, $type = 'text')
    {
        return Helper::dataGet($this->data, $key, $default);
    }


    public function cookie($key, $default = null)
    {
        if (isset($_COOKIE[$key])) {
            return $_COOKIE[$key];
        }

        return $default;
    }

    public function session($key, $default = null)
    {
        if (isset($_SESSION[$key])) {
            return $_SESSION[$key];
        }

        return $default;
    }

    public function server($key, $default = null)
    {
        if (isset($_SERVER[$key])) {
            return $_SERVER[$key];
        }

        return $default;
    }

    public function all()
    {
        return $this->data;
    }

    public static function collapse($array)
    {
        $results = [];

        foreach ($array as $values) {
            if (!is_array($values)) {
                continue;
            }

            $results[] = $values;
        }

        return array_merge([], ...$results);
    }

    private static function sanitizeArray($value)
    {
        return static::sanitizeArrayRecursively($value);
    }

    private static function sanitizeArrayRecursively($arr)
    {
        $newArr = array();

        foreach ($arr as $key => $value) {
            $newArr[$key] = (is_array($value) ? static::sanitizeArrayRecursively($value) : sanitize_text_field($value));
        }

        return $newArr;
    }

    public function getMessage($field, $rule_name, $messages)
    {
        $message_key = "{$field}.{$rule_name}";

        if (isset($messages[$message_key])) {
            return $messages[$message_key];
        }

        return null;
    }

    public function validate($object)
    {
        $rules_array = $object->rules($this);
        $messages = $object->messages();

        $validator = $this->getValidator();

        foreach ($rules_array as $field => $rules) {
            foreach ($rules as $rule) {
                if (is_string($rule)) {
                    $message = $this->getMessage($field, $rule, $messages);
                    $message ? $validator->rule($rule, $field)->message($message) : $validator->rule($rule, $field);
                } else if (is_array($rule)) {
                    $message = $this->getMessage($field, $rule[0], $messages);

                    if (is_array($rule[1])) {
                        $message ? $validator->rule($rule[0], $field, $rule[1])->message($message) : $validator->rule($rule[0], $field, $rule[1]);
                    } else {
                        $rest = $rule;
                        //this will remove and rearrange the index
                        array_shift($rest);
                        $message ? $validator->rule($rule[0], $field, ...$rest)->message($message) : $validator->rule($rule[0], $field, ...$rest);
                    }
                }
            }
        }

        if (!$validator->validate()) {
            $errors = $validator->errors();
            Response::success($errors, 422);
        }
    }

    public function getValidator()
    {
        if (is_object(Request::$validator)) {
            return Request::$validator;
        }

        $data = $this->all();

        Request::$validator = new Validator($data);

        $this->addCustomRules();

        return Request::$validator;
    }

    public function addCustomRules()
    {
        if ($this->customRuleInstance instanceof Rules) {
            $this->customRuleInstance->addCustomRules();
        }
    }
}
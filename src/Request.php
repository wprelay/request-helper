<?php

namespace Cartrabbit\Request;

use Cartrabbit\Request\Validation\Rules;
use Valitron\Validator;

class Request
{

    protected $query;
    protected $post;
    protected $cookies;

    public static $validator;

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

    public function __construct()
    {
        [$get, $post] = $this->getInputs();
        $this->initialize($get, $post, $_COOKIE);
    }

    protected static $request;

    public static function make()
    {
        if (!isset(self::$request)) {
            return self::$request = new self();
        }

        return self::$request;
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


    private function initialize($get = [], $post = [], $cookies = [])
    {
        $this->query = new InputBag($get);
        $this->post = new InputBag($post);
        $this->cookies = new InputBag($cookies);
    }

    public function get($key, $default = null, $type = 'text')
    {
        $value = $this->getFromRequest($key, $default);

        $type = gettype($value);

        if (!in_array($type, array_keys(static::$sanitizeCallbacks))) {
            throw new \UnexpectedValueException('The sanitization Type is Not Present in the request class');
        }

        return call_user_func(static::$sanitizeCallbacks[$type], $value);
    }

    public function getFromRequest($key, $default = null)
    {
        if ($this->query->has($key)) {
            return $this->query->get($key);
        } else if ($this->post->has($key)) {
            return $this->post->get($key);
        } else {
            $value = $this->all();
        }

        if (is_array($value)) {
            return Helper::dataGet($value, $key, $default);
        } else {
            return $value;
        }
    }


    public function addError($key, $message)
    {
        $this->errors[$key] = $message;
    }

    public function all()
    {
        return array_merge($this->query->all(), $this->post->all(), $this->cookies->all());
    }

    private function getInputs()
    {
        $method = $_SERVER['REQUEST_METHOD'];
        $get = [];
        $post = [];
        switch (strtoupper($method)) {
            case 'GET':
                $get = $_GET;
            case 'POST':
            case 'PUT':
            case 'PATCH':
            case 'DELETE':
                $post = $this->getUserInputs();
        }

        return [$get ?? [], $post ?? []];
    }

    private function getUserInputs()
    {
        if (!isset($_SERVER['CONTENT_TYPE'])) {
            $_SERVER['CONTENT_TYPE'] = 'application/x-www-form-urlencoded';
        }

        if ($_SERVER['CONTENT_TYPE'] == 'application/x-www-form-urlencoded' || strpos($_SERVER['CONTENT_TYPE'], 'application/x-www-form-urlencoded') !== false) {
            $post = $_POST;
        } else {
            $json = file_get_contents('php://input');

            $data = json_decode($json, true);
            $post = $data;
        }

        return $post;
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

    public static function sanitizeArray($value)
    {
        return $value;
    }


    public function validate($object, array $additionalRules = [])
    {

        if (is_object($object)) {
            $rules = array_merge($object->rules($this), $additionalRules);
        } else {
            $rules = array_merge($object, $additionalRules);
        }


        $validator = $this->getValidator();

        $validator->mapFieldsRules($rules);

//        $validator = $this->mapCustomErrorMessages($validator, $rules, $messages);

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

    public function mapCustomErrorMessages($validator, $rules, $messages)
    {
        return $validator;

//        foreach ($rules as $key => $rule) {
//            foreach ($rule as $ruleType) {
//                if (is_array($ruleType)) {
//                    $type = $ruleType[0];
//                } else {
//                    $type = $ruleType;
//                }
//
//                $messageKey = "{$key}.{$type}";
//
//                if (!in_array($messageKey, array_keys($messages))) continue;
//
////                var_dump([$key, $type, $messages[$messageKey]]);
//
//                $validator->rule($type, $key)->message($messages[$messageKey]);
//            }
//        }
    }

}
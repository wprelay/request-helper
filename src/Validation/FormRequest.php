<?php

namespace Cartrabbit\Request\Validation;


use Cartrabbit\Request\Request;

interface FormRequest
{
    public function rules(Request $request);

    public function messages();
}
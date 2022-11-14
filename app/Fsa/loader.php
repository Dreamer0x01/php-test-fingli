<?php
//создадим синглтон для удобной работы c функциями получения данных с сайта ФСА

if (!function_exists('fsaparse')) {
    function fsaparse() : \app\Fsa\Classes\Fsaparse
    {
        global $fsaparse_instance;
        if (!isset($fsaparse_instance))
            $fsaparse_instance = \app\Fsa\Classes\Fsaparse::getInstance(new \fl\curl\Curl(
                [
                    CURLOPT_SSL_VERIFYPEER => FALSE
                ]
            ));
        return $fsaparse_instance;
    }
}

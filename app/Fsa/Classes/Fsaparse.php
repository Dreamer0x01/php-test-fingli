<?php

namespace app\Fsa\Classes;
use fl\curl\Curl;

//собрали все функции для работы с сайтом ФСА в одном слассе. Доступ к нему сделаем через синглтон в автолоадере (композер сам всё обновит)
class Fsaparse
{
    private static $instance;  // экземпляр объекта
    protected Curl $curl;
    protected $targetUrl;
    protected bool $connected;

    private function __construct(Curl $curl)
    {
        $this->curl = $curl;
        $this->connected = false;
        session_start();
    }

    private function __clone()
    { /* ... @return Singleton */
    }  // Защищаем от создания через клонирование

    private function __wakeup()
    { /* ... @return Singleton */
    }  // Защищаем от создания через unserialize

    public static function getInstance(Curl $curl)
    {    // Возвращает единственный экземпляр класса. @return Singleton
        if (empty(self::$instance)) {
            self::$instance = new self($curl);
        }
        return self::$instance;
    }

    /**
     * @param string $targetUrl
     * @description шлем запрос к ФСА с нужными параметрами авторизации, сохраняем в экземпляр класса полученный токен
     * @return bool
     */
    public function Connect(string $targetUrl, array $connectParams) : bool
    {
        $this->targetUrl = $targetUrl;
        $responsePost = $this->curl->setBody(
            $connectParams ,
            true
        )->post("{$this->targetUrl}/login");

        if(!preg_match('/Authorization:[\s]*Bearer[\s]*([\S]+)/', $responsePost->header, $matches))
        {
            return false;
        }
        $token = $matches[1];
        $this->curl->setHeader('Authorization', "Bearer $token");
        $this->connected = true;
        return true;
    }

    /**
     * @return array|null
     * @description запрашиваем массив значений для фильтов
     */
    public function getFiltersFields() : ?array
    {
        if(!$this->connected)
            return null;
        $responseGet = $this->curl->get("{$this->targetUrl}/api/v1/rds/common/identifiers");
        $resp =  json_decode($responseGet->body, true);
        $statusNames = [];
        foreach ($resp['status'] as $alias => $statusData)
            $statusNames[ $statusData['id'] ] = ['alias' => $alias, 'name' => $statusData['name']];

        $_SESSION['statusNames'] = $statusNames; // сохраним их в сессию, так как по условиям задачи нам нужно будет возвращать только поля, которые есть в таблице
        //без этого требования я бы не стал хранить именно так, а разобрал бы уровнем выше id статуса, сохранив данные фильтров в джаваскрипте, так как они запрашивались ранее
        return $resp;
    }

    /**
     * @param array $columns - массив полей, которые нужно вернуть в результирующей выборке
     * @description вернет выборку в соответствии с полученными из запроса значениями фильтров. По условию задачи требуется эту выборку порезать, убрав поля, которых нет в таблице
     * @return mixed|null
     */
    public function RequestFilteredData(array $columns)
    {
        if(!$this->connected)
            return null;

        //данные для запроса сформируем из фильтров максимально похоже на то, как это делается на сайте ФСА. Структуру массива соответственно воспроизводим похожим образом
        $requestData = [
            'filter' => $_POST['filter'],
            'size' => $_POST['size'],
            'page' => 0
        ];

        $requestData['filter']['columnsSearch'][0] += ['name' => 'number', 'translated' => 'false', 'type' => 8] ;//тоже издержки структуры запроса ФСА

        $responsePost = $this->curl->setBody(
            $requestData,
            true
        )->post("{$this->targetUrl}/api/v1/rds/common/declarations/get");
        $respValues =  json_decode($responsePost->body, true);

        //вот теперь из анее сохраненных занчений фильтров нужно вытащить статусы и вставить в ответ. Без условия сохранения только определенных колонок можно было бы спокойно отдать id и обработать чуть выше, так как названя статусов уже есть в джаваскрипте
        if(isset($_SESSION['statusNames']) && isset($respValues['items']))
        {
            foreach($respValues['items'] as &$item)
            {
                $item['statusName'] = $_SESSION['statusNames'][$item['idStatus']]['name']; //меняем числовые индексы статусов на текстовые
                $item = array_intersect_key($item, array_flip($columns));//убираем то, что не требуется возвращать
            }
        }
        return $respValues;
    }


}






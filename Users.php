<?php


class Users extends User
{
    protected $id = array();

    public function __construct($db, $params = [], $compare = [])
    {
        //Проверка наличия класса от которого наследуется
        if (!class_exists('User', false)) {
            throw new LogicException("Unable to load class: User");
        }

        // Сохранение класса для работы с БД
        $this->db = $db;

        // Валидация параметров запроса
        $paramsValidate = array();
        foreach ($params as $param => $value) {
            $paramsValidate[$param] = $this->validate($param, $value);
        }

        // Выборка людей по параметрам
        $results = $this->getUsers($paramsValidate, $compare);
        foreach ($results as $result) {
            $this->id[] = $result['id'];
        }
    }


    /**
     * Получение массива экземпляров класса User из массива id класса Users
     * Возвращает экземпляры класса User
     */
    public function getArrayUsers()
    {
        $users = array();
        foreach ($this->id as $id) {
            $params = $this->getUsers(['id' => $id]);
            unset($params[0]['id']);
            $data = new DateTime($params[0]['birthdate']);
            $params[0]['birthdate'] = $data->format("d.m.Y");
            $users[] = new User($this->db, $params[0]);
        }
        return $users;
    }

    public function deleteAll()
    {
        $ids = $this->id;
        foreach ($ids as $id) {
            $this->delete($id);
        }
    }
}
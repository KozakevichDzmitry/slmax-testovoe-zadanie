<?php

class User
{
    protected $db;
    private $id;
    private $name;
    private $surname;
    private $birthdate;
    private $gender;
    private $city;


    public function __construct($db, $userInfo)
    {
        // Сохранение класса для работы с БД
        $this->db = $db;
        // Инициализация полей класс
        $this->init($userInfo);
        //Добавление человека в БД с проверкой на дубликат
        $user = $this->existInDB();
        if (empty($user)) {
            $this->id = $this->save();
        } else {
            $this->id = $user[0]['id'];
        }
    }

    private function init($userInfo)
    {
        foreach ($userInfo as $param => $value) {
            $this->$param = $this->validate($param, $value);
        }
    }


    /**
     * Проверка существует ли человек в БД.
     * Возвращает данные из БД или null
     */
    private function existInDB()
    {
        $params = [
            'name' => $this->name,
            'surname' => $this->surname,
            'birthdate' => $this->birthdate,
            'gender' => $this->gender,
            'city' => $this->city,
        ];
        return $this->getUsers($params);
    }


    /**
     * Сохранение полей экземпляра класса ($id, $name, $surname, $birthdate, $gender, $city) в БД.
     * Возвращает id сохранения в БД
     */
    private function save()
    {
        $query = 'INSERT INTO `users` ( name, surname, birthdate, gender, city ) 
                  VALUES ( :name, :surname, :birthdate, :gender, :city )';
        $params = [
            'name' => $this->name,
            'surname' => $this->surname,
            'birthdate' => $this->birthdate,
            'gender' => $this->gender,
            'city' => $this->city,
        ];
        try {
            $db = $this->db->connect();
            $stmt = $db->prepare($query);
            foreach ($params as $key => $value) {
                $stmt->bindValue(":$key", $value);
            }
            $stmt->execute();
            return $db->lastInsertId();
        } catch (PDOException $e) {
            throw new Exception($e->getMessage());
        } finally {
            $this->db->disconnect();
        }

    }


    /**
     * Удаление человека из БД по id
     */
    public function delete($id=null)
    {
        if(!empty($this->id)) {
            $id= $this->id;
        }
        try {
            $query = "DELETE FROM `users` WHERE id = {$id}";
            $stmt = $this->db->connect()->prepare($query);
            $stmt->execute();
        } catch (PDOException $e) {
            throw new Exception($e->getMessage());
        } finally {
            $this->db->disconnect();
        }
    }


    /**
     * Поучение людей из БД по параметрам.
     * Вызов без параметров возвращает всю таблицу
     */
    protected function getUsers($params, $compare = [])
    {
        if ($params) {
            $query = self::query("SELECT * FROM `users` WHERE", $params, $compare);
        } else {
            $query = "SELECT * FROM `users`";
        }
        try {
            $stmt = $this->db->connect()->prepare($query);
            $stmt->execute($params);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            throw new Exception($e->getMessage());
        } finally {
            $this->db->disconnect();
        }
    }


    /**
     * Формирует строку запрос к БД
     */
    static function query($query, $params, $compare)
    {
        $keys = array_keys($params);
        for ($i = 0; $i < count($keys); $i++) {
            if (array_key_exists($keys[$i], $compare)) $operator = $compare[$keys[$i]];
            else $operator = '=';

            $query .= " $keys[$i] $operator :$keys[$i]";
            if (count($keys) - 1 !== $i) $query .= ' &&';
        }
        return $query;
    }


    /**
     * Преобразование даты рождения в возраст (полных лет)
     */
    public static function getAge($date)
    {
        $birthdate = new DateTime($date);
        $now = new DateTime();
        $interval = $now->diff($birthdate);
        return $interval->format('%y');
    }


    /**
     * Преобразование пола из двоичной системы в текстовую (1 - муж, 0 - жен)
     */
    public static function genderToString($gender = null)
    {
        if ($gender === 0) {
            return 'жен';
        } else if ($gender === 1) {
            return 'муж';
        } else {
            throw new Exception("Input 0 or 1");
        }
    }


    /**
     * Валидация входных данных
     * $param:
     *      name, surname - только буквы
     *      birthdate - дата в формате '01.01.2022'
     *      gender - 0 или 1
     */
    protected function validate($param, $value)
    {
        if ($param === 'name' || $param === 'surname') {
            if (!preg_match("/^[a-zA-Zа-яёА-ЯЁ]+$/u", $value)) {
                throw new Exception("Name is invalid");
            }
            return $value;
        } elseif ($param === 'birthdate') {
            $data = trim($value);
            $dataArr = explode('.', $data);
            if (!checkdate($dataArr[1], $dataArr[0], $dataArr[2])) {
                throw new Exception("Birthdate is invalid (Valid format: '01.01.2022')");
            }
            $data = new DateTime($data);
            return $data->format("Y-m-d");
        } elseif ($param === 'gender') {
            if ($value != 0 && $value != 1) {
                throw new Exception("Gender is invalid. Input 0 or 1(0-муж, 1-жен)");
            }
            return $value;
        }
        return $value;
    }


    /**
     * Форматирование данных с преобразованием возраста и (или) пола.
     * Возвращает новый экземпляр stdClass
     */
    public function format()
    {
        $gender = self::genderToString($this->gender);
        $birthdate = self::getAge($this->birthdate);
        return (object)[
            'id' => $this->id,
            'name' => $this->name,
            'surname' => $this->surname,
            'birthdate' => $birthdate,
            'gender' => $gender,
            'city' => $this->city,
        ];
    }
}
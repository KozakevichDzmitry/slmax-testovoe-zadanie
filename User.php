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
        $this->db = $db;
        $this->init($userInfo);
        $user = $this->getUsers();
        if (empty($user)) {
            $this->save();
            $this->id = $this->db->lastInsertId();
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

    private function save()
    {
        $query = 'INSERT INTO `users` ( name, surname, birthdate, gender, city ) 
                  VALUES ( :name, :surname, :birthdate, :gender, :city )';
        $params = [
            'name'      => $this->name,
            'surname'   => $this->surname,
            'birthdate' => $this->birthdate,
            'gender'    => $this->gender,
            'city'      => $this->city,
        ];

        try {
            $db = $this->db->connect();
            $stmt = $db->prepare($query);
            foreach ($params as $key => $value) {
                $stmt->bindValue(":$key", $value);
            }
            $stmt->execute();
            return true;
        } catch (PDOException $e) {
            throw new Exception($e->getMessage());
        } finally {
            $this->db->disconnect();
        }

    }

    public function delete($ids = array())
    {
        if (!$ids) {
            if (!$this->id) throw new Exception('Users not found');
            $ids = array($this->id);
        }
        try {
            foreach ($ids as $id) {
                $query = "DELETE FROM `users` WHERE id = {$id}";
                $stmt = $this->db->connect()->prepare($query);
                $stmt->execute();
            }
        } catch (PDOException $e) {
            throw new Exception($e->getMessage());
        } finally {
            $this->db->disconnect();
        }

    }

    protected function getUsers($params = [], $compare = [])
    {
        if (!$params) {
            $params = [
                'name'      => $this->name,
                'surname'   => $this->surname,
                'birthdate' => $this->birthdate,
                'gender'    => $this->gender,
                'city'      => $this->city,
            ];
        }
        $query = self::query("SELECT * FROM `users` WHERE", $params, $compare);

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

    public static function getAge($date)
    {
        $birthdate = new DateTime($date);
        $now = new DateTime();
        $interval = $now->diff($birthdate);
        return $interval->format('%y');
    }

    protected function validate($type, $value)
    {
        if ($type === 'name') {
            if (!preg_match("/^[a-zA-Zа-яёА-ЯЁ]+$/u", $value)) {
                throw new Exception("Name is invalid");
            }
            return $value;
        } elseif ($type === 'birthdate') {
            $data = trim($value);
            $dataArr = explode('.', $data);
            if (!checkdate($dataArr[1], $dataArr[0], $dataArr[2])) {
                throw new Exception("Birthdate is invalid (Valid format: '01.01.2022')");
            }
            $data = new DateTime($data);
            return $data->format("Y-m-d");
        } elseif ($type === 'gender') {
            if ($value != 0 && $value != 1) {
                throw new Exception("Gender is invalid. Input 0 or 1(0-муж, 1-жен)");
            }
            return $value;
        }
        return $value;
    }

    public function format()
    {
        $gender = self::genderToString($this->gender);
        $birthdate = self::getAge($this->birthdate);
        return (object)[
            'id'        => $this->id,
            'name'      => $this->name,
            'surname'   => $this->surname,
            'birthdate' => $birthdate,
            'gender'    => $gender,
            'city'      => $this->city,
        ];
    }
}
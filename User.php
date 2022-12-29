<?php

class User
{
    const GENDER_MALE = 1;
    const GENDER_FEMALE = 0;

    private static array $genders = [
        self::GENDER_MALE => 'Male',
        self::GENDER_FEMALE => 'Female',
    ];

    private $db;
    private int $id;
    private string $name;
    private string $surname;
    private string $birthdate;
    private int $gender;
    private string $city;

    public function __construct($db, $userInfo)
    {
        // Saving a class for working with a database
        $this->db = $db;

        if(is_array($userInfo)){
            $this->validate($userInfo);
            $params = $this->prepareParams($userInfo);
            $userID = $this->getUserID($params);
            if (!$userID) {
                $userID = $this->save($params);
            }
            $params['id'] = $userID;
            $this->setParams($params);
        }elseif(is_int($userInfo)){ // if it's a number, then it's an id
            $user = $this->getUserById($userInfo);
            if(empty($user)){
                throw new Exception('User by this id not found');
            }
            $this->setParams($user);
        }

    }

    /**
     * Sets the passed values to class parameters
     */
    private function setParams($data): void
    {
        $this->id = (int)$data['id'];
        $this->name = $data['name'];
        $this->surname = $data['surname'];
        $this->gender = (int)$data['gender'];
        $this->city = $data['city'];
        $this->birthdate = $data['birthdate'];
    }

    /**
     * Returns id or null from DB
     * @throws Exception
     */
    private function getUserID($userInfo): ?string
    {
        $params = [
            'name' => $userInfo['name'],
            'surname' => $userInfo['surname'],
            'birthdate' => $userInfo['birthdate'],
            'gender' => $userInfo['gender'],
            'city' => $userInfo['city'],
        ];
        $user = $this->getUser($params);

        return $user ? $user['id'] : null;
    }

    /**
     * Saving class instance fields ($id, $name, $surname, $birthdate, $gender, $city) in DB.
     * Returns id from DB
     * @throws Exception
     */
    private function save($userInfo): string
    {
        $query = 'INSERT INTO `users` ( name, surname, birthdate, gender, city ) 
                  VALUES ( :name, :surname, :birthdate, :gender, :city )';
        $params = [
            'name' => $userInfo['name'],
            'surname' => $userInfo['surname'],
            'birthdate' => $userInfo['birthdate'],
            'gender' => $userInfo['gender'],
            'city' => $userInfo['city'],
        ];
        try {
            $db = $this->db->connect();
            $statement = $db->prepare($query);
            foreach ($params as $key => $value) {
                $statement->bindValue(":$key", $value);
            }
            $statement->execute();
            return $db->lastInsertId();
        } catch (PDOException $e) {
            throw new Exception($e->getMessage());
        } finally {
            $this->db->disconnect();
        }
    }

    /**
     * Delete user from DB
     * @throws Exception
     */
    public function delete(): bool
    {
        $id = $this->id;

        try {
            $query = "DELETE FROM `users` WHERE id = {$id}";
            $statement = $this->db->connect()->prepare($query);
            $statement->execute();
        } catch (PDOException $e) {
            throw new Exception($e->getMessage());
        } finally {
            $this->db->disconnect();
        }
        return true;
    }

    /**
     * Get user from DB by ID.
     * @throws Exception
     */
    public function getUserById($id): ?array
    {
        $query = "SELECT * FROM `users` WHERE id = :id";
        $param = ['id' => $id];

        try {
            $statement = $this->db->connect()->prepare($query);
            $statement->execute($param);
            $result = $statement->fetchAll();
            if(empty($result)){
                return null;
            }
            return $result[0];
        } catch (PDOException $e) {
            throw new Exception($e->getMessage());
        } finally {
            $this->db->disconnect();
        }
    }

    /**
     * Get user from DB by params.
     * @throws Exception
     */
    private function getUser($params): ?array
    {
        if(empty($params)){
            throw new Exception('Function parameters are not set');
        }

        $query = self::query("SELECT * FROM `users` WHERE", $params);

        try {
            $statement = $this->db->connect()->prepare($query);
            $statement->execute($params);
            $result = $statement->fetchAll();
            if(empty($result)){
                return null;
            }
            return $result[0];
        } catch (PDOException $e) {
            throw new Exception($e->getMessage());
        } finally {
            $this->db->disconnect();
        }
    }

    /**
     * Preparing parameters for a query
     */
    public static function prepareParams($params): array
    {
        if( array_key_exists( 'birthdate' ,$params ) ) {
            $data = new DateTime(trim($params['birthdate']));
            $params['birthdate'] = $data->format("Y-m-d");
        }
        return $params;
    }

    /**
     * Generates a query string to the database
     */
    public static function query($query, $params): string
    {
        $keys = array_keys($params);
        for ($i = 0; $i < count($keys); $i++) {
            $query .= " $keys[$i] = :$keys[$i]";
            if (count($keys) - 1 !== $i) {
                $query .= ' &&';
            }
        }
        return $query;
    }

    /**
     * Conversion of date of birth to age (full years)
     */
    public static function getAge($date): int
    {
        $birthdate = new DateTime($date);
        $now = new DateTime();
        $interval = $now->diff($birthdate);
        return $interval->format('%y');
    }

    /**
     * Converting gender from binary to text system (1 - male, 0 - female)
     * @throws Exception
     */
    public static function genderToString($gender = null): string
    {
        if (isset(static::$genders[$gender])) {
            return static::$genders[$gender];
        } else {
            throw new Exception("Input 0 or 1");
        }
    }

    /**
     * Validation of input data
     * $param:
     *      name, surname - only letters
     *      birthdate - date in the format '01.01.2022'
     *      gender - 0 or 1
     * @throws Exception
     */
    protected function validate($userInfo): bool
    {
        foreach ($userInfo as $param => $value) {
            if ($param === 'name' or $param === 'surname') {
                if (!preg_match("/^[a-zA-Zа-яёА-ЯЁ]+$/u", $value)) {
                    throw new Exception("Name is invalid");
                }
            } elseif ($param === 'birthdate') {
                $data = trim($value);
                $dataArr = explode('.', $data);
                if (!checkdate($dataArr[1], $dataArr[0], $dataArr[2])) {
                    throw new Exception("Birthdate is invalid (Valid format: '01.01.2022')");
                }
            } elseif ($param === 'gender') {
                if (!in_array($value, array_keys(static::$genders))) {
                    throw new Exception("Gender is invalid. Input 0 or 1(0-муж, 1-жен)");
                }
            }
        }
        return true;
    }


    /**
     * Formatting of data with conversion of age and (or) gender.
     * Returns a new instance stdClass
     * @throws Exception
     */
    public function format(): StdClass
    {
        $gender = self::genderToString($this->gender);
        $birthdate = self::getAge($this->birthdate);

        $user = new StdClass;
        $user->id = $this->id;
        $user->name = $this->name;
        $user->surname = $this->surname;
        $user->birthdate = $birthdate;
        $user->gender = $gender;

        return $user;
    }
}
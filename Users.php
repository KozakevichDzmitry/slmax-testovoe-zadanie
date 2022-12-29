<?php


class Users
{
    private array $ids;
    private array $searchParams = [
        'name',
        'surname',
        'birthdate',
        'gender',
        'city',
        'id',
    ];
    private array $searchCompares = ['>', '<', '<=', '>=', '='];

    public function __construct($db, $params = [], $compare = [])
    {

        // Saving a class for working with a database
        $this->db = $db;

        // Validation of request parameters
        $this->validate($params, $compare);

        // Get users ids from DB
        $ids = $this->getIdsUsers($params, $compare);

        foreach ($ids as $id) {
            $this->ids[] = $id["id"];
        }

    }

    /**
     * Checking whether such parameters and comparison operators are available
     * @throws Exception
     */
    private function validate($paramsValidate, $comparesValidate): bool
    {
        foreach ($paramsValidate as $param => $value) {
            if (!in_array($param, $this->searchParams)) {
                throw new Exception("Parameter '$param' is not valid");
            }
        }
        foreach ($comparesValidate as $comparer) {
            if (!in_array($comparer, $this->searchCompares)) {
                throw new Exception("Parameter '$comparer' is not valid");
            }
        }
        return true;
    }

    /**
     * Getting people from the DB by parameters.
     * Call without parameters returns all the ids from the table DB.
     * @throws Exception
     */
    protected function getIdsUsers($params, $compare = []): array
    {
        if ($params) {
            $params = $this->prepareParams($params);
            $query = self::query("SELECT id FROM `users` WHERE", $params, $compare);
        } else {
            $query = "SELECT id FROM `users`";
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
     * Generates a query string to the database
     */
    static function query($query, $params, $compare): string
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
     * Preparing parameters for a query
     */
    static function prepareParams($params): array
    {
        if (array_key_exists('birthdate', $params)) {
            $data = new DateTime(trim($params['birthdate']));
            $params['birthdate'] = $data->format("Y-m-d");
        }
        return $params;
    }

    /**
     * Getting an array of instances of the User class from the ids array of the Users class
     * Returns instances of the User class
     */
    public function getArrayUsers(): array
    {
        $users = [];
        foreach ($this->ids as $id){
            $users[] = (new User($this->db, $id));
        }
        return $users;
    }

    /**
     * Deletes all users from the database with ids that are stored in ids
     */
    public function deleteAll(): bool
    {
        $users= $this->getArrayUsers();
        foreach ($users as $user) {
            $user->delete();
        }
        return true;
    }
}
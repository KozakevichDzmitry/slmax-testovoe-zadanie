<?php


class Users extends User
{
    protected $id = array();

    public function __construct($db, $params = [], $compare = [])
    {
        $this->db = $db;
        $paramsValidate = array();
        foreach ($params as $param => $value) {
            $paramsValidate[$param] = $this->validate($param, $value);
        }
        $results = $this->getUsers($paramsValidate, $compare);
        foreach ($results as $result) {
            $this->id[] = $result['id'];
        }
    }

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
        $this->delete($this->id);
    }

    public function getIds()
    {
        return $this->id;
    }

}
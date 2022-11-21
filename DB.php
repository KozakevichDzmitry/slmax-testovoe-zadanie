<?php
class DB
{
    private $host;
    private $dbname;
    private $login;
    private $password;
    private $isConnect;

    public function __construct($dbinfo)
    {

        $this->host = $dbinfo['host'];
        $this->dbname = $dbinfo['dbname'];
        $this->login = $dbinfo['login'];
        $this->password = $dbinfo['password'];

    }

    public function connect()
    {

        try {
            $this->db = new PDO("mysql:host={$this->host};dbname={$this->dbname}", $this->login, $this->password);
            $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            $this->isConnect = true;
            return $this->db;
        } catch (PDOException $e) {
            throw new Exception($e->getMessage());
        }
    }

    public function disconnect()
    {
        if($this->isConnect){
            $this->db = null;
            $this->isConnect = false;
        }
    }

}
<?php
class DB
{
    private string $host;
    private string $dbname;
    private string $login;
    private string $password;
    private bool $isConnect;
    private ?PDO $db;

    public function __construct($dbinfo)
    {

        $this->host = $dbinfo['host'];
        $this->dbname = $dbinfo['dbname'];
        $this->login = $dbinfo['login'];
        $this->password = $dbinfo['password'];

    }

    public function connect() : PDO
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

    public function disconnect() :void
    {
        if($this->isConnect){
            $this->db = null;
            $this->isConnect = false;
        }
    }

}
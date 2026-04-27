<?php

class User {
    protected $name;
    protected $email;
    protected $role;

    public function __construct($name, $email, $role) {
        $this->name = $name;
        $this->email = $email;
        $this->role = $role;
    }

    public function getName() { return $this->name; }
    public function getEmail() { return $this->email; }
    public function getRole() { return $this->role; }
}


class Student extends User {
    public function __construct($name, $email) {
        parent::__construct($name, $email, 'user');
    }
}

class Admin extends User {
    public function __construct($name, $email) {
        parent::__construct($name, $email, 'admin');
    }
}
?>
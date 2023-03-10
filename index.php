<?php
require_once('./dbinfo.php');
require_once('./DB.php');
require_once('./User.php');
// Checking for the presence of the class from which it is inherited
if (!class_exists('User', false)) {
    throw new LogicException("Unable to load class: User");
}
require_once('./Users.php');
$userInfo1 = [
    'name'      => 'Дима',
    'surname'   => 'Козакевич',
    'birthdate' => '02.04.1991',
    'gender'    => 1,
    'city'      => 'Minsk'
];
$userInfo2 = [
    'name'      => 'Dima',
    'surname'   => 'Kozakevich',
    'birthdate' => '02.04.1995',
    'gender'    => 1,
    'city'      => 'Minsk'
];
$userInfo3= [
    'name'      => 'Lily',
    'surname'   => 'Smith',
    'birthdate' => '02.04.2000',
    'gender'    => 0,
    'city'      => 'Akron'
];

$db = new DB(dbinfo());

$user1 = new User($db, $userInfo1);;
$user2 = new User($db, $userInfo2);
$user3 = new User($db, $userInfo3);

//print_r(new User($db, 151));
//var_dump($user1->delete());


//$users = new Users($db, ['gender' => 1, 'birthdate' => '02.04.1995',], ['birthdate' => '<=']);
//$users = new Users($db);
//print_r($users);
//print_r($users->getArrayUsers());
//$users->deleteAll();
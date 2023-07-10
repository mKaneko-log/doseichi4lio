<?php
require_once('./dbwrapper.php');

$p = $_POST;
$return = null;

switch ($_POST['status']) {
    case 'login':
        $return = 'hello!';
        break;
    
    case 'send':
        break;
    
    case 'logout':
        break;
}

if($return !== null) echo(json_encode($return));

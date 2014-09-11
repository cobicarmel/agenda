<?

require '../Global.lib.php';

require 'actions.lib.php';

/* getting the requested operation */

[$_POST, $_GET, $_REQUEST];

$clientData = &${ACCEPT_METHOD};

$subject = $clientData['subject'];

$action = $clientData['action'];

$constParams = [];

if(isset($clientData['constParams']))
	$constParams = $clientData['constParams'];

unset($clientData['subject'], $clientData['action'], $clientData['constParams']);

/* filtering input */

Database::filterInput($clientData);

/* performing action */

require_once 'class.' . $subject . '.php';

$permission = $actions[$subject][$action];

$reflection = new ReflectionClass($subject);

$instance = $reflection->newInstanceArgs($constParams);

$instance -> $action();

/* returning result */

Database::getResponse(true);
<?php
$access_item = false;
//$access_item["/list/"] = true;

if(isset($read_access) && $read_access) {
	return;
}

include_once($_SERVER["FRAMEWORK_PATH"]."/config/init.php");


$action = $page->actions();
$model = new Navigation();


$page->bodyClass("navigation");
$page->pageTitle("Navigation");


if(is_array($action) && count($action)) {

	// LIST ITEM
	if(count($action) >= 1 && $action[0] == "list") {

		$page->page(array(
			"type" => "admin",
			"templates" => "admin/navigation/list.php"
			)
		);
		exit();

	}
	// NEW ITEM
	else if(count($action) == 1 && $action[0] == "new") {

		$page->page(array(
			"type" => "admin",
			"templates" => "admin/navigation/new.php"
			)
		);
		exit();

	}
	// EDIT ITEM
	else if(count($action) == 2 && $action[0] == "edit") {

		$page->page(array(
			"type" => "admin",
			"templates" => "admin/navigation/edit.php"
			)
		);
		exit();

	}

	// ADD NAVIGATION NODE
	else if(count($action) == 2 && $action[0] == "new_node") {

		$page->page(array(
			"type" => "admin",
			"templates" => "admin/navigation/new_node.php"
			)
		);
		exit();

	}
	// EDIT NAVIGATION NODE
	else if(count($action) == 2 && $action[0] == "edit_node") {

		$page->page(array(
			"type" => "admin",
			"templates" => "admin/navigation/edit_node.php"
			)
		);
		exit();

	}

	// Class interface
	else if($page->validateCsrfToken() && preg_match("/[a-zA-Z]+/", $action[0])) {

		// check if custom function exists on User class
		if($model && method_exists($model, $action[0])) {

			$output = new Output();
			$output->screen($model->$action[0]($action));
			exit();
		}
	}

}

$page->page(array(
	"templates" => "pages/404.php"
	)
);

?>

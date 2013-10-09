<?php
include_once(dirname(__FILE__)."/utilities.php");
add_action('wp_ajax_my_action', 'my_action_callback');

function my_action_callback() {
global $wpdb; // this is how you get access to the database
$roleInstance = new roleAssignmentRules();
$output = array();
$returnVal = "";
if(isset($_POST))
{
	if(isset($_POST["option"]) && isset($_POST["ruleName"]) && !empty($_POST["ruleName"]))
	{
		$ruleName = $_POST['ruleName'];
		$source = isset($_POST["source"]) ? $_POST["source"] : "";
		$query = isset($_POST["query"]) ? $_POST["query"] : "";
	    $attributeName = isset($_POST["attributeName"]) ? $_POST["attributeName"] : "";
	    $operator = isset($_POST["operator"]) ? $_POST["operator"] : "";
	    $comparedValue = isset($_POST["comparedValue"]) ? $_POST["comparedValue"] : "";
	    $wpRole = isset($_POST["wpRole"]) ? $_POST["wpRole"] : "";
	    $wpSite = isset($_POST["wpSite"]) ? $_POST["wpSite"] : "";
	    error_log("Name of rule ++++++++++++ :".$ruleName);
	   
		switch($_POST["option"])
		{
			case "addNupdate":
				$post = array("source" => $source, "attributeName" => $attributeName, "operator" => $operator, "comparedValue" => $comparedValue, "wpRole" => $wpRole, "query" => $query, "wpSite" => $wpSite);
				
				error_log("POst++++++++++++ :".print_r($post,true));
				$returnVal = $roleInstance->addNupdateRule($ruleName, $post);
				break;
			case "view":
				$returnVal = $roleInstance->getRule($ruleName);
				break;
			case "edit":
				$returnVal = $roleInstance->htmlTemplate($ruleName);
				break;
			case "default":
				break;
		}
	}
}
$output = array("returnValue" => $returnVal);

echo json_encode($output);



die(); // this is required to return a proper result
}





?>
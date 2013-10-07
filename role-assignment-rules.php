<?php
include_once(dirname(__FILE__)."/utilities.php");
class roleAssignmentRules
{
	/*
		Handles form submit
	*/
	const RULE_ROLES_KEY = "wp_cas_roles_rules";


	public $update_function = "update_option";
	public $get_function = "get_option";
	

	public function __contruct ()
	{
		init();
	}

	public function init()
	{
		if(is_multisite())
		{
			$this->update_function = "update_site_option";
			$this->get_function = "get_site_option";
		}
	}


	public function addNupdateRule($name,$post)
	{
		//$roleRuleOb = new roleAssignmentRules();
		$this->init();
		$allRules = $this->getAllRules();
		$funcUsed = $this->update_function;
		if($allRules)
		{
			$allRules[$name] = $post;
			error_log("name :".$name);
			error_log("post :".$post);
			return $funcUsed(roleAssignmentRules::RULE_ROLES_KEY,$allRules);
		}
		else
		{
			$newRule = array();
			$newRule[$name] = $post;
			return $funcUsed(roleAssignmentRules::RULE_ROLES_KEY,$newRule);
		}
		return false;
	}

	public function getRule($rule)
	{
		$this->init();
		$allRules = $this->getAllRules();
		error_log("all rules $$$$$$$$$$$$$$$$$$$$$$$$$$$$$ :".print_r($allRules,true));
		
		if(isset($allRules[$rule]) && !empty($allRules[$rule]))
		{
			return $allRules[$rule];
			
		}
		return false;
	}
	
	public function getAllRules()
	{
		$this->init();
		$funcUsed = $this->get_function;
		return $funcUsed(roleAssignmentRules::RULE_ROLES_KEY);
	}

	public function getAllRuleNames()
	{
		$allRules = $this->getAllRules();
		if($allRules)
			return array_keys($allRules);
		
		return false;
	}




	public function htmlTemplate($rule=null)
	{
		//settings_fields( 'wpcasldap' );
		$ruleAttributes = $this->getRule($rule);
		



		error_log("Check name rule -------------------". __('Name of Rule','wpcasldap'));
		$ruleHtml = "";
		
		$ruleHtml .= '<div>';
		$ruleHtml .=  '<label>'. __('Name of Rule','wpcasldap') .'</label>';

		$ruleHtml .=  '<input type="text" size="50" name="wpcasldap_rulename" id="rulename_inp" value="'.$rule.'" >';
		$ruleHtml .= '</div>';

		$ruleHtml .= '<div>';
		$ruleHtml .= '<label>'.__('Choose Source','wpcasldap').'</label>';
		$ruleHtml .= '<select name="wpcasldap_source" id="source_inp">';
		$source = isset($ruleAttributes["source"]) ? $ruleAttributes["source"] : "";
		if($source =="CAS")
			$ruleHtml .= '<option value="CAS" selected>CAS</option>';
		else
			$ruleHtml .= '<option value="CAS">CAS</option>';
		if($source =="LDAP")
			$ruleHtml .= '<option value="LDAP" selected>LDAP</option>';
		else
			$ruleHtml .= '<option value="LDAP">LDAP</option>';
		
		$ruleHtml .= '</select>';
		$ruleHtml .= '</div>';

		$ruleHtml .= '<div>';
		$ruleHtml .= '<label>'.__('Attribute Name','wpcasldap').'</label>';
		$att_name = isset($ruleAttributes["attributeName"]) ? $ruleAttributes["attributeName"] : "";
		$ruleHtml .= '<input type="text" size="50" name="wpcasldap_attribute_name" id="attribute_name_inp" value='.$att_name.'>';
		$ruleHtml .= '</div>';

		$ruleHtml .= '<div>';
		$ruleHtml .= '<label>'.__('Comparison Operator','wpcasldap').'</label>';
		$ruleHtml .= '<select name="wpcasldap_operator" id="operator_inp">';
		$operator = isset($ruleAttributes["operator"]) ? $ruleAttributes["operator"] : "";
		if($operator == "is")
			$ruleHtml .= '<option value="is" selected >is</option>';
		else
			$ruleHtml .= '<option value="is">is</option>';

		if($operator == "is not")
			$ruleHtml .= '<option value="is not" selected >is not</option>';
		else
			$ruleHtml .= '<option value="is not">is not</option>';
		$ruleHtml .= '</select>';
		$ruleHtml .= '</div>';

		$ruleHtml .= '<div>';
		$ruleHtml .= '<label>'.__('Compared Value','wpcasldap').'</label>';
		$compared_value = isset($ruleAttributes["comparedValue"]) ? $ruleAttributes["comparedValue"] : "";
		$ruleHtml .= '<input type="text" size="50" name="wpcasldap_compared_value" id="compared_value_inp" value='.$compared_value.'>';
		$ruleHtml .= '</div>';

		$ruleHtml .= '<div>';
		$ruleHtml .= '<label>'.__('WP Role','wpcasldap').'</label>';
		$ruleHtml .= '<select name="wpcasldap_wp_role" id="wp_role_inp">';
		$wp_role = isset($ruleAttributes["wpRole"]) ? $ruleAttributes["wpRole"] : "";
		foreach (get_editable_roles() as $role_name => $role_info):
			$selected = "";
			if($wp_role == $role_name)
			{
				$selected = "selected";
			}
			$ruleHtml .= '<option value="'.$role_name.'" '.$selected .'>'.$role_name.'</option>';
		endforeach;
		//Get the option list from the database
		$ruleHtml .= '</select>';
		$ruleHtml .= '</div>';

		$ruleHtml .= '<div>';
		$ruleHtml .= '<label>'.__('WP Site','wpcasldap').'</label>';
		$ruleHtml .= '<select name="wpcasldap_wp_site" id="wp_site_inp">';
		$wp_site = isset($ruleAttributes["wpSite"]) ? $ruleAttributes["wpSite"] : "";
		if( $wp_site == "select all")
			$ruleHtml .= '<option value="select all" selected>select all</option>';
		else
			$ruleHtml .= '<option value="select all">select all</option>';
		//Get the site list from the database
		$blog_list = get_blog_list( 0, 'all' );
		krsort($blog_list);
		$wp_site = isset($ruleAttributes["wpSite"]) ? $ruleAttributes["wpSite"] : "";
		foreach ($blog_list AS $blog):
			$selected = "";
			if( $wp_site == $blog['path'])
				$selected = "selected";
			$ruleHtml .= '<option value="'.$blog['path'].'"  '. $selected .' >'.$blog['path'].'</option>';
		endforeach;	
		$ruleHtml .= '</select>';
		$ruleHtml .= '</div>';

		$ruleHtml .= '<div>';
		$ruleHtml .= '<input type="button" name="wpcasldap_create_rule" id="create_rule_inp" class="button-primary" value="Save Settings" >';
		$ruleHtml .= '</div>';


		debug_log("rule html :".$ruleHtml);

		return $ruleHtml;
	}
}


?>

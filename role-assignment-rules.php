<?php
include_once(dirname(__FILE__)."/utilities.php");
include_once(dirname(__FILE__)."/ldapManager.php");
include_once(dirname(__FILE__)."/cas-password-encryption.php");
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
		if(isset($name) && isset($post) && !empty($name) && !empty($post))
		{
			$this->init();
			$allRules = $this->getAllRules();
			$funcUsed = $this->update_function;
			$rulesCount = 0;
			$post['rid'] = 1;
			if($allRules)
			{
				$rulesCount = count($allRules);
				if(array_key_exists ($name, $allRules))
					$rid = $allRules[$name]["rid"];
				else
					$rid = $rulesCount + 1;

				$post['rid'] = $rid;	
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

    public function applyRules($username)
    {
        error_log("###############START OF RULE ROLES ###################");
        error_log("username :".$username);
        $role = "";
        $allRules = $this->getAllRules();
        foreach($allRules as $key => $value)
        {
            $evaluateResult = $this->evaluateRule($allRules[$key],$username);
            error_log("\n  search results from rule :".print_r($evaluateResult,true));
            if(is_array($evaluateResult) && $evaluateResult["status"] == "true")
            {
               if(isset($evaluateResult["role"]) && !empty($evaluateResult["role"]))
                {
                    $role = $evaluateResult["role"];
                    break;
                }
            }


        }
        error_log("############### END OF RULE ROLES ###################");
        return $role;
    }

    public function evaluateRule($rule,$username)
    {
        if(isset($rule) && !empty($rule))
        {
            //get all the information of a rule
            $source = $rule["source"];
            $query = $rule["query"];
            $query = str_replace("%u",$username,$query);

            $attributeName = $rule["attributeName"];
            $operator = $rule["operator"];
            $comparedValue = $rule["comparedValue"];
            $comparedValue = str_replace("%u",$username,$comparedValue);
            $wpRole = $rule["wpRole"];
            $funcUsed = $this->get_function;
            $ldapBaseDn = $funcUsed("wpcasldap_ldapbasedn");
            $ldapUsername = $funcUsed("wpcasldap_ldapuser");
            $ldapPassword = $funcUsed("wpcasldap_ldappassword");
            $ldapUri = $funcUsed("wpcasldap_ldapuri");
            global $ciphers;
            if(!empty($ldapPassword))
                $ldapPassword = wpcasclient_decrypt($ldapPassword,$ciphers);
            if(empty($ldapUsername) || empty($ldapPassword) || empty($ldapBaseDn))
            {
                debug_log("Ldap Username, Ldap Password or Ldap BaseDN not configured properly");
                return null;
            }

            $ldapMOb = new ldapManager($ldapUsername, $ldapPassword,null,$ldapUri);
            //currently attributeName is not an array, so we need to make it because ldap_search accepts attribute as an array

            $attributes = array($attributeName);
            //error_log("search input value : basedn - ".$ldapBaseDn. "   query- ".$query."  attributes: ".print_r($attributes,true));
            $searchResults = $ldapMOb->executeLdapQuery($ldapBaseDn,$query,$attributes);
            error_log("search results :".print_r($searchResults,true));
            $output = array();
            if(isset($searchResults) && is_array($searchResults))
            {
                for($i=0;$i<count($searchResults)-1;$i++)
                {
                    $attributeName = strtolower($attributeName);
                    error_log("first element:".print_r($searchResults[$i][$attributeName],true));
                    $output[] = $searchResults[$i][$attributeName][0];
                }

            }
            if(isset($output) && is_array($output))
            {

                // TODO: Currently just doing in_array comparison
                // TODO: This needs to be updated to use the operator which the user provided


                if(in_array($comparedValue,$output))
                {
                    //Rule passed
                    return array("status"=> "true" , "role" => $wpRole);

                }
            }
            return array("status"=> "false" , "role" => $wpRole);


        }


        return null;
    }




    public function operatorInterpretation($operator)
    {

        return null;
    }






	public function htmlTemplate($rule=null)
	{
		//settings_fields( 'wpcasldap' );
		$ruleAttributes = $this->getRule($rule);
		



		error_log("Check name rule -------------------". __('Name of Rule','wpcasldap'));
		$ruleHtml = "<div>"; // This is div is used to get the elements in javacript. Do not remove this.
		
		$ruleHtml .= '<div>';
		$ruleHtml .=  '<label>'. __('Name of Rule','wpcasldap') .'</label>';
		$rulename_id = "rulename";

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

		$ruleHtml .= '<div id="query_div" style="display:none;">';
		$ruleHtml .= '<label>'.__('LDAP Query','wpcasldap').'</label>';
		$ruleHtml .= '<input type="text" name="wpcasldap_ldap_query" id="ldap_query" >';
		$ruleHtml .= '</div>';





		$ruleHtml .= '<div>';
		$ruleHtml .= '<label>'.__('Attribute Name','wpcasldap').'</label>';
		$att_name = isset($ruleAttributes["attributeName"]) ? $ruleAttributes["attributeName"] : "";
		$ruleHtml .= '<input type="text" size="50" name="wpcasldap_attribute_name" id="attribute_name_inp" value="'.$att_name.'" >';
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
		$ruleHtml .= '<input type="text" size="50" name="wpcasldap_compared_value" id="compared_value_inp" value="'.$compared_value.'" >';
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

		$wp_sites = $ruleAttributes["wpSite"];


		$ruleHtml .= '<div>';
		$ruleHtml .= '<label>'.__('WP Site','wpcasldap').'</label><br/>';
		$blog_list = get_blog_list( 0, 'all' );
		krsort($blog_list);
		foreach ($blog_list AS $blog):
			$checked = "";
			if(is_array($wp_sites) && in_array($blog["path"],$wp_sites))
			{
				$checked = "checked";
			}
			$ruleHtml .= '<input type="checkbox" name="wpcasldap_wp_site" value="'. $blog["path"] .'" '.$checked.' /> <label>'. $blog["path"].'</label><br/>';
		endforeach;	

		$ruleHtml .= '</div>';
		
		if( isset($ruleAttributes["rid"]) && $ruleAttributes["rid"] > 0)
		{
			$ruleHtml .= '<input type="hidden" name="rid" value="'.$ruleAttributes["rid"].'">';
		}
		$ruleHtml .= '<div>';
		$ruleHtml .= '<input type="button" class="button-primary" value="Save Settings" >';
		$ruleHtml .= '</div>';
		$ruleHtml .= '</div>';


		debug_log("rule html :".$ruleHtml);

		return $ruleHtml;
	}
}


?>

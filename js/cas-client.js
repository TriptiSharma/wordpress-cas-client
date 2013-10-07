function showPasswordField()
{
	var buttonReset = document.getElementById("reset");
	var ldapPasswordField = document.getElementById("ldap_password_inp");
	ldapPasswordField.style.display = "";
	 ldapPasswordField.disabled = false;
	 buttonReset.style.display = "none";
}

jQuery(document).ready(function($) {
  jQuery(".button-primary").click(function() {  
    // validate and process form here  
    var ruleName = jQuery("input#rulename_inp").val();
    var source = jQuery("#source_inp option:selected").text();
    var attributeName = jQuery("input#attribute_name_inp").val();
    var operator = jQuery("#operator_inp option:selected").text();
    var comparedValue = jQuery("input#compared_value_inp").val();
    var wpRole = jQuery("#wp_role_inp option:selected").text();
    var wpSite = jQuery("#wp_site_inp option:selected").text();
    var ajax_url = jQuery("#ajax_url").val();
    var data = {
		action: 'my_action',
		option: 'addNupdate',
		ruleName: ruleName,
		source: source,
	    attributeName: attributeName,
	    operator: operator,
	    comparedValue: comparedValue,
	    wpRole: wpRole,
	    wpSite: wpSite
	}


    jQuery.post(ajax_url, data, function(response) {
		alert('Got this from the server: ' + response);
	});

  });  


  jQuery(".role_rules").click(function() { 
  	var ruleName = jQuery(this).text();
  	console.log("rule name "+ruleName);
  	var ajax_url = jQuery("#ajax_url").val();
  	var data = {
		action: 'my_action',
		option: 'view',
		ruleName: ruleName		
	}

    jQuery.post(ajax_url, data, function(response) {
		
		if(response)
		{
			var parsedJson = JSON.parse(response);
			var ruleArray = parsedJson.returnValue;
			//console.log("0 :"+ruleArray["source"]);	
			html_el = "Source - "+ruleArray["source"]+"<br/>";
			html_el += "Attribute Name - "+ruleArray["attributeName"]+"<br/>";
			html_el += "operator - "+ruleArray["operator"]+"<br/>";
			html_el += "Compared Value - "+ruleArray["comparedValue"]+"<br/>";
			html_el += "WP Role - "+ruleArray["wpRole"]+"<br/>";


			var divId = ruleName.replace(' ',"_");
			//console.log("div Id :"+divId);
			jQuery("#"+divId).html(html_el);

		}
	});
  });



  jQuery(".edit_rule").click(function() {
  		var parent = jQuery(this).parent();
  		var firstChild = parent.children(":first");
  		var ruleName = firstChild.text();
  		console.log("rule name :"+ruleName);
  		var ajax_url = jQuery("#ajax_url").val();
	  	var data = {
			action: 'my_action',
			option: 'edit',
			ruleName: ruleName		
		}

		jQuery.post(ajax_url, data, function(response) {
		
		if(response)
		{
			console.log("response :"+response);
			var parsedJson = JSON.parse(response);
			var allHtml = parsedJson.returnValue;
  			var divId = ruleName.replace(' ',"_");
  			jQuery("#"+divId).html(allHtml);
  		}
	});
  });
}); 


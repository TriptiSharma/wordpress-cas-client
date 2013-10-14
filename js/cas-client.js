function showPasswordField()
{
	var buttonReset = document.getElementById("reset");
	var ldapPasswordField = document.getElementById("ldap_password_inp");
	ldapPasswordField.style.display = "";
	 ldapPasswordField.disabled = false;
	 buttonReset.style.display = "none";
}

jQuery(document).ready(function($) {
	jQuery(".button-primary").live('click', function(){
  //jQuery(".button-primary").click(function() {  
    // validate and process form here  
    //alert("hello");
    var ruleName = jQuery("input#rulename_inp").val();
    var source = jQuery("#source_inp option:selected").text();
    var attributeName = jQuery("input#attribute_name_inp").val();
    var operator = jQuery("#operator_inp option:selected").text();
    var comparedValue = jQuery("input#compared_value_inp").val();
    var wpRole = jQuery("#wp_role_inp option:selected").text();
    var wpSite = new Array();
    var query = jQuery("input#ldap_query").val();
   // console.log("element :"+jQuery('input[name="wpcasldap_wp_site"]:checked');
    //console.log(jQuery('input[name="wpcasldap_wp_site"]:checked').serialize());
    jQuery('input[name="wpcasldap_wp_site"]:checked').each(function() {
	   console.log(this.value);
	   wpSite.push(this.value);
	});
	console.log("wp site :"+wpSite);
   
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
	    wpSite: wpSite,
        query: query
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
			html_el += "WP Site - <br/>";
			if(ruleArray["wpSite"] != undefined)
			{
				for(var i=0;i<ruleArray["wpSite"].length;i++)
				{
					html_el += ruleArray["wpSite"][i] +"<br/>";
				} 
			}

			var divId = ruleName.replace(' ',"_");
			//console.log("div Id :"+divId);
			jQuery("#"+divId).html(html_el);

		}
	});
  });

 jQuery("#source_inp").change(function(){
 	var optionSelected = jQuery("option:selected", this);
 	var valueSelected = this.value;
 	console.log("value selected :"+ valueSelected);
 	if(valueSelected == "LDAP")
 		jQuery("#query_div").css("display","");
 	else
 	{
 		jQuery("#query_div").css("display","none");
 		jQuery("#ldap_query").val("");
 	}
 });






  jQuery(".edit_rule").click(function() {
  		var parent = jQuery(this).parent();
  		var firstChild = parent.children(":first");
  		var ruleName = firstChild.text();
  		console.log("rule name :"+ruleName);
  		var ajax_url = jQuery("#ajax_url").val();
	  	var data = {
			action: 'my_action',
			option: 'view',
			ruleName: ruleName		
		}

		jQuery.post(ajax_url, data, function(response) {
		
		if(response)
		{
			console.log("response :"+response);
			var parsedJson = JSON.parse(response);
			var returnValue = parsedJson.returnValue;
  			jQuery("#rulename_inp").val(ruleName);
  			console.log("source :"+returnValue["source"]);
  			jQuery("#source_inp").val(returnValue["source"]);
  			jQuery("#source_inp").trigger("change");
  			if(returnValue["source"]=="LDAP")
  			{
  				jQuery("#ldap_query").val(returnValue["query"]);
  			}

  			jQuery("#attribute_name_inp").val(returnValue["attributeName"]);
  			jQuery("#operator_inp").val(returnValue["operator"]);
  			jQuery("#compared_value_inp").val(returnValue["comparedValue"]);
  			jQuery("#wp_role_inp").val(returnValue["wpRole"]);
  			var wpSites = returnValue["wpSite"];
  			console.log("wpsites :"+wpSites);
  			 jQuery('input[name="wpcasldap_wp_site"]').each(function() {
  			 	if(jQuery.inArray(this.value,wpSites) > -1)
  			 			jQuery(this).attr('checked','checked');
  			 });
  		}
	});
  });
}); 


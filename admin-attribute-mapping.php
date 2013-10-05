<?php
/**
 * Created by JetBrains PhpStorm.
 * User: ssouth
 * Date: 10/4/13
 * Time: 4:55 PM
 * To change this template use File | Settings | File Templates.
 */


function AttributeMappingHtml()
{
  // TODO: retrieve these parameters from the plugin config (see casManager::GetMappings() for more info)
  $attributeHtml = AttributeHtml("CAS", "sn", "last_name", "test query", "test base DN");

  // Using the string heredoc syntax: http://www.php.net/manual/en/language.types.string.php#language.types.string.syntax.heredoc
  $html = <<<HTML
<div class="metabox-holder">
    <div style="width: 99.5%" class="postbox-container">
        <div  class="postbox "  id="">
            <div class="handlediv" title="">
                <br />
            </div>

            <h3>Mappings</h3>
            <div class="inside">
            $attributeHtml
            </div> <!-- . inside -->
        </div> <!-- .postbox -->
        <p class="submit">
            <input type="submit" class="button-secondary" name="" value="Create new rule" />
        </p>
    </div> <!-- .postbox-container -->
</div> <!-- .metabox-holder -->
HTML;

  return $html;
}

function AttributeHtml($service, $attributeName, $wpField, $query = "", $baseDN = "")
{
  // TODO: populate $wpFieldList with available WP User fields
  $wpFieldList = array("first_name" => "First Name", "last_name" => "Last Name");
  // TODO: populate $wpFieldList with available WP User fields
  $serviceList = array("LDAP" => "LDAP", "CAS" => "CAS");

  $wpFieldOptions = BuildOptionList($wpFieldList, $wpField);
  $serviceOptions = BuildOptionList($serviceList, $service);

  // Using the string heredoc syntax: http://www.php.net/manual/en/language.types.string.php#language.types.string.syntax.heredoc
  $html = <<<HTML
                <table class="form-table">
                    <tr valign="top">
                        <th scope="row">
                            <label>
                                Source:
                            </label>
                        </th>

                        <td>
                            <select name="" id="">
                                $serviceOptions
                            </select>
                        </td>
                    </tr>

                    <tr valign="top">
                        <th scope="row">
                            <label>
                                Base DN:
                            </label>
                        </th>

                        <td>
                            <input type="text" size="50" name="" id="" value="$baseDN" />
                        </td>
                    </tr>

                    <tr valign="top">
                        <th scope="row">
                            <label>
                                Query:
                            </label>
                        </th>

                        <td>
                            <textarea cols="45" rows="4" name="" id="" value="$query"></textarea>
                        </td>
                    </tr>

                    <tr valign="top">
                        <th scope="row">
                            <label>
                                Attribute:
                            </label>
                        </th>

                        <td>
                            <input type="text" size="50" name="" id="" value="$attributeName" />
                        </td>
                    </tr>


                    <tr valign="top">
                        <th scope="row">
                            <label>
                                WP User field:
                            </label>
                        </th>

                        <td>
                            <select name="" id="">
                                $wpFieldOptions
                            </select>
                        </td>
                    </tr>

                    <tr valign="top">
                        <th scope="row">
                            <td>
                                <p class="submit">
                                    <input type="submit" class="button-primary" name="" value="Save rule" />
                                    <input type="submit" class="button-secondary" style="float: right;" name="" value="Delete rule" />
                                </p>
                            </td>
                        </th>
                    </tr>

                </table>
HTML;
  return $html;
}

function BuildOptionList($fieldList, $selectedField)
{
  $optsList = "";
  foreach($fieldList as $fieldKey => $fieldText)
  {
    $selected = $fieldKey == $selectedField ? " selected='true'" : "";
    $optsList .= "<option value='$fieldKey'$selected>$fieldText</option>";
  }
  return $optsList;
}
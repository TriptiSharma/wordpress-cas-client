<?php
/**
 * Created by JetBrains PhpStorm.
 * User: ssouth
 * Date: 9/25/13
 * Time: 5:07 PM
 * To change this template use File | Settings | File Templates.
 */

include_once(dirname(__FILE__) . "/utilities.php");
include_once(dirname(__FILE__) . "/ldapUser.php");

/**
 * Class ldapManager
 */
class ldapManager
{
  /**
   * The URL scheme to use for unencrypted LDAP connections
   */
  const URI_SCHEME = "ldap";
  /**
   * The URL scheme to use for encrypted SSL LDAP connections
   */
  const SSL_URI_SCHEME = "ldaps";
  /**
   * The default port to use for unencrypted LDAP connections
   */
  const DEFAULT_PORT = "389";
  /**
   * The default port to use for encrypted SSL LDAP connections
   */
  const SSL_DEFAULT_PORT = "636";
  /**
   * Option flag for setting the protocol version.
   */
  const OPT_PROTOCOL_VERSION = LDAP_OPT_PROTOCOL_VERSION;

  // fields
  /**
   * The LDAP connection
   *
   * @var null
   */
  private $connection = null;

  /**
   * @var string
   */
  private $password = "";

  //  unset properties
  /**
   * The URL to use for LDAP connections.
   *
   * @var string
   */
  public $Uri;

  /**
   * @var string
   */
  public $Username;

  public $ProfileMappings = array();

  public $Query = "";

  /**
   * @param        $login
   * @param        $password
   * @param array  $mappings
   * @param string $uri
   * @param string $query
   */
  function __construct($login, $password, array $mappings = null, $uri= "", $query = "")
  {
    debug_log("Initializing ldapManager (constructor) [login => '$login', password => '$password', uri => '$uri', query => '$query', mappings => ".print_r($mappings,true)." ]");

    $this->Username = $login;
    $this->password = $password;

    if($uri != "") {
//      debug_log("(ldapManager) Setting Uri = '$uri''");
      $this->Uri = $uri;
//      debug_log("(ldapManager) this->Uri == '$this->Uri'");
    }
    if($query != "") {
      $this->Query = $query;
    }
    else
    {
      $this->Query = "sAMAccountName={id}";
    }

    if(!isset($mappings))
    {
      // set default mappings
      $this->ProfileMappings = array(
        new ldapProfileMapping("user_login", "sAMAccountName"),
        new ldapProfileMapping("user_email", "mail"),
        new ldapProfileMapping("first_name", "givenname"),
        new ldapProfileMapping("last_name", "sn"),
        new ldapProfileMapping("nickname", "cn"),
        new ldapProfileMapping(null, "uid"),
        new ldapProfileMapping(null, "EmployeeID"),
      );
    }
    else
    {
      $this->ProfileMappings = $mappings;
    }
    debug_log("ProfileMappings: ".print_r($this->ProfileMappings, true));
  }

  /**
   *
   */
  function __destruct()
  {
    $this->Close();
  }

  // interface methods
  /**
   * Connects to an LDAP server.
   *
   * @param string $uri
   *
   * @return null|resource
   */
  public function Connect($uri = "")
  {
    if(!$this->HaveUri($uri)) { return null; }

    debug_log("(ldapManager->Connect()) Establishing LDAP connection: " . $this->Uri . "...");

    try
    {
      $uri_parts = ldapManager::ParseUri($this->Uri);
      $scheme = empty($uri_parts["scheme"]) ? ldapManager::URI_SCHEME : $uri_parts["scheme"];

      if ($scheme == ldapManager::SSL_URI_SCHEME)
      {
        debug_log("(ldapManager->Connect()) LDAPS detected. (scheme == '$scheme')");
        $port = $this->SetPort($uri_parts, ldapManager::SSL_DEFAULT_PORT);
      }
      else
      {
        debug_log("(ldapManager->Connect()) LDAPS not specified - establishing UNENCRYPTED connection.");
        $port = $this->SetPort($uri_parts, ldapManager::DEFAULT_PORT);
      }

// Wordpress install complains that http_build_url() is not recognized. 9/30/2013 - shawn.south@bellevuecollege.edu
//      $connection_url = http_build_url("", $uri_parts, HTTP_URL_STRIP_PORT);
      $connection_url = $this->BuildUrl($scheme, $uri_parts);
      debug_log("(ldapManager->Connect()) Connection URL: '" . $connection_url . "' on port [" . $port . "]");

      $this->connection = ldap_connect($connection_url, $port);
    }
    catch (Exception $ex)
    {
      $error_msg = "LDAP connection to '" . $this->Uri . "' failed: " . $ex->getMessage();
      error_log($error_msg);
      debug_log("(ldapManager->Connect()) ".$error_msg);
    }
    return $this->connection;
  }

  /**
   * Closes an LDAP connection.
   */
  public function Close()
  {
    if (isset($this->connection))
    {
      ldap_close($this->connection);
    }
    else
    {
      debug_log("No connection exists, so call to Close() ignored.");
    }
  }

  /**
   * Binds an LDAP connection by logging in.
   *
   * @param $login
   * @param $password
   *
   * @return bool
   */
  public function Bind($login, $password)
  {
    if (isset($this->connection))
    {
      return @ldap_bind($this->connection, $login, $password);
    }
    error_log("Unable to Bind() to LDAP until a connection has been established.");
    return false;
  }

  /**
   * Sets an LDAP connection option.
   *
   * @param $flag
   * @param $value
   *
   * @return bool
   */
  public function SetOption($flag, $value)
  {
    if (isset($this->connection))
    {
      return ldap_set_option($this->connection, $flag, $value);
    }
    error_log("Unable to set LDAP options until a connection has been established.");
    return false;
  }

  /**
   * Performs an LDAP query.
   *
   * @param $base_dn
   * @param $query
   *
   * @return null|resource
   */
  public function Search($base_dn, $query, $attributes = null)
  {
    if(!isset($base_dn) || !isset($query))
    {
      debug_log("(ldapManager->Search()) Must provide a Base DN ($base_dn) and query ($query) to Search.");
      error_log("Unable to search LDAP without both a Base DN and query.");
      return null;
    }
    // TODO: Merge GetSearchResults() with Search(), so that one call returns ldap entries.
    if(!$this->HaveConnection("ldapManager->Search()", $this->connection))
    {
      return null;
    }

      //bind
      /*
      $bind = $this->Bind($this->Username, $this->password);

      error_log("bind value :".$bind);
      //$bind = @ldap_bind($ds);
      //Check to make sure we're bound.
      if (!$bind) {
          $error = 'Binding to LDAP failed.';
          echo "\nERROR: " . $error;
          error_log($error);
          debug_log("(ldapManager->GetUser()) ".$error);
          return null;
          //exit();
      } */
    if(!isset($attributes))
    {
        // extract an array of just the attribute values we'll need from LDAP
        $attributes = array_map(create_function('$v', 'return $v->attribute;'), array_values($this->ProfileMappings));
        debug_log("(ldapManager->Search()) Attributes to retrieve from LDAP ".array_info($attributes));
    }

    // TODO: do we need additional attributes for internal logic? (see the constructor)
    // e.g. 'EmployeeID', 'rolename'?

     // TODO: is $filter the Query?
    return ldap_search($this->connection, $base_dn, $query, $attributes);
  }

  /**
   * Parses results returned from a call to Search()
   *
   * @param $search_results
   *
   * @return array|null
   */
  public function GetSearchResults($search_results)
  {
    // TODO: Merge GetSearchResults() with Search(), so that one call returns ldap entries.
    if (isset($this->connection))
    {
      return ldap_get_entries($this->connection, $search_results);
    }
    $error = "Unable to retrieve LDAP search results until a connection has been established.";
    error_log($error);
    debug_log("(ldapManager->GetSearchResults()) ".$error);
    return null;
  }

  /**
   * Parses a URI into its component parts.
   *
   * @param $uri
   *
   * @return mixed
   */
  public static function ParseUri(&$uri)
  {
    debug_log("Parsing URI: '$uri'");
    $components = parse_url($uri);
    debug_log("Parsed URI components: " . print_r($components, true));

    if (empty($components['host']) && !empty($components['path']))
    {
      debug_log("Empty 'host', but non-empty 'path' (".$components['path'].")");

      $ldap_uri = ldapManager::URI_SCHEME. "://". $uri;
      debug_log("url: " . $ldap_uri);

      $components = parse_url($ldap_uri);
      debug_log("components after editing uri:" . print_r($components, true));
    }
    return $components;
  }

  /**
   * @param        $uid
   *
   * @param string $baseDN
   *
   * @return bool|ldapUser
   */
  function GetUser($uid, $baseDN)
  {
    try
    {
      $ds = $this->Connect();
      if (!$ds) {
        $error = 'Error in contacting the LDAP server.';
        error_log("\n" . $error);
        debug_log("(ldapManager->GetUser()) ".$error);
      } else {
        //error_log("\n".$filter);
        /*
        $ldap_dn = $wpcasldap_use_options['ldapbasedn'];
        */
        //echo "<h2>Connected</h2>";

        // Make sure the protocol is set to version 3
        if (!$this->SetOption(ldapManager::OPT_PROTOCOL_VERSION, 3)) {
          $error = 'Failed to set protocol version to 3.';
          error_log("\n" . $error);
          debug_log("(ldapManager->GetUser()) ".$error);
        } else {
          debug_log("(ldapManager->GetUser()) username: '" . $this->Username . "', Password: '".  $this->password . "'");
          if(empty($this->Username) || empty($this->password))
          {
            echo "ERROR: LDAP Username or LDAP Password not configured correctly";
            exit();
          }

          $bind = $this->Bind($this->Username, $this->password);
          //$bind = @ldap_bind($ds);
          //Check to make sure we're bound.
          if (!$bind) {
            $error = 'Binding to LDAP failed.';
            echo "\nERROR: " . $error;
            error_log($error);
            debug_log("(ldapManager->GetUser()) ".$error);
            //exit();
          } else {

            /*
            This code is added to get all the groups a users belongs to.
            */
            /*
            $GroupsDN = array();
            $filter = "sAMAccountName=".$uid;
            $attributes_ad = array("dn","givenName","sn","primaryGroupID");
            //Query to get Primary group id
            $search = ldap_search($ds, $wpcasldap_use_options['ldapbasedn'], $filter,$attributes_ad);
            $result = ldap_get_entries($ds, $search);
            error_log( "result:".print_r($result,true));

            $pri_grp_rid = $result[0]['primarygroupid'][0];
            echo "primaryGroupID :".$pri_grp_rid ;

            $r = ldap_read($ds, $wpcasldap_use_options['ldapbasedn'], '(objectclass=*)', array('objectSid')) or exit('ldap_search');
            $data = ldap_get_entries($ds, $r);
            $domain_sid = $data[0]['objectsid'][0];
            echo "<br/> domain sid:".$domain_sid;
            $domain_sid_s = sid2str($domain_sid);
            echo "<br/> domain sid s:".$domain_sid_s;
            //Request to get Primary group CN
            $r = ldap_search($ds, $wpcasldap_use_options['ldapbasedn'], "objectSid=${domain_sid_s}-${pri_grp_rid}", array('cn')) or exit('ldap_search');
            $data = ldap_get_entries($ds, $r);
            error_log("\n data:".print_r($data,true));
            //exit();

            $defaultGroupDN = $data[0]['dn'];
            $getCN = $data[0]['cn'][0];
            //$defaultGroupDN = "CN=".$getCN.",OU=Groups,DC=BellevueCollege,DC=EDU" ;
            echo "<br/> CN:".$getCN;


            echo("<br/> dn = ".$defaultGroupDN."\n");
            // This query is to get all the groups which are memberOf Primary Group
            //Its not working right now.
            if($defaultGroupDN !=null)
            {
                $GroupsDN[] = $defaultGroupDN ;
                $filter = "(memberof:1.2.840.113556.1.4.1941:=".$defaultGroupDN.")";
                $attributes_ad = array("CN");
                $search = ldap_search($ds, $wpcasldap_use_options['ldapbasedn'], $filter,$attributes_ad);
                $info = ldap_get_entries($ds, $search);
                echo("<br/>".print_r($info,true));
                for($i=0;$i<count($info);$i++)
                {

                        if($info[$i]["dn"] !=null)
                            $GroupsDN[] = $info[$i]["dn"] ;
                        echo(print_r("<br/>".$info[$i]["dn"],true) ."<br/>");

                    //var_dump($info[$i]);
                }
            }

            if($result[0]["dn"] !=null)
            {
                $filter = "(member:1.2.840.113556.1.4.1941:=".$result[0]["dn"].")";
                $attributes_ad = array("CN");
                $search = ldap_search($ds, $wpcasldap_use_options['ldapbasedn'], $filter,$attributes_ad);
                $info = ldap_get_entries($ds, $search);

                //error_log("\nresult identifier :".$info);
                error_log("\nenterries :".print_r($info,true));
                echo "count :".count($info);
                for($i=0;$i<count($info);$i++)
                {

                        if($info[$i]["dn"] !=null)
                            $GroupsDN[] = $info[$i]["dn"] ;
                        echo(print_r($info[$i]["dn"],true) ."<br/>");

                    //var_dump($info[$i]);
                }
                //var_dump($info);
                exit();
            }

            */
            $query = str_replace("{id}", $uid, $this->Query);

            debug_log("(ldapManager->GetUser()) Searching for user ID '$uid' (LDAP query: '$query')");
            $search = $this->Search($baseDN, $query);
            if (isset($search) && !empty($search))
            {
              $info = $this->GetSearchResults($search);

              $this->Close();
              // TODO: Is this code assuming that $info only contains one record?
              return new ldapUser($info);
            }
            debug_log("(ldapManager->GetUser()) User not found!");
          }
          $this->Close();
        }
      }
    }
    catch (Exception $e)
    {
      $err_msg = "An LDAP error occurred while talking to '" . $this->Uri . "': " . $e->getMessage();
      error_log($err_msg);
      debug_log("(ldapManager->GetUser()) ".$err_msg);
    }
    return false;
  }

  // Private methods

  /**
   * Sets the connection port.
   *
   * @param $uri_parts
   * @param $defaultPort
   *
   * @internal param $port
   * @return string
   */
  private function SetPort($uri_parts, $defaultPort)
  {
    if(empty($uri_parts["port"]))
    {
      return $defaultPort;
    }
    $port = $uri_parts["port"];

    $is_valid = is_numeric($port) || intval($port) > 0;

    if (!$is_valid)
    {
      debug_log("No port specified - using default (" . $defaultPort . ")");
      return $defaultPort;
    }
    return $port;
  }

  /**
   * Constructs a URL from its component parts.
   *
   * @param $scheme
   * @param $uri_parts
   *
   * @return string
   */
  public function BuildUrl($scheme, $uri_parts)
  {
    $hostpath = empty($uri_parts["host"]) ? $uri_parts["path"] : $uri_parts["host"] . (empty($uri_parts["path"]) ? "" : $uri_parts["path"]);
    return $scheme . "://" . $hostpath;
  }

  /**
   * @param $uri
   * @return bool
   */
  private function HaveUri($uri = "")
  {
    if ($uri != "")
    {
      $this->Uri = $uri;
    }
    else
    {
      debug_log("No URL provided ($uri), falling back on Uri property ($this->Uri)");
      if (isset($this->Uri) && $this->Uri != "")
      {
        return true;
      }
      else
      {
        $error = "Unable to continue - no LDAP URI was provided.";
        error_log($error);
        debug_log($error);
      }
    }
  }

  private function HaveConnection($source, &$connection)
  {
    if (isset($connection))
    {
      return true;
    }
    else
    {
      debug_log("($source) Don't have an active connection, attempting to connect");
      $connection = $this->Connect();

      if (isset($connection))
      {
        return true;
      }
      else
      {
        $error = "Unable to search LDAP until a connection has been established.";
        error_log($error);
        debug_log("($source) $error");
      }
    }
    return false;
  }


    function executeLdapQuery($baseDN,$query,$attributes)
    {
        try
        {
            $ds = $this->Connect();
            if (!$ds) {
                $error = 'Error in contacting the LDAP server.';
                error_log("\n" . $error);
                debug_log("(ldapManager->GetUser()) ".$error);
            } else {
                //error_log("\n".$filter);
                /*
                $ldap_dn = $wpcasldap_use_options['ldapbasedn'];
                */
                //echo "<h2>Connected</h2>";

                // Make sure the protocol is set to version 3
                if (!$this->SetOption(ldapManager::OPT_PROTOCOL_VERSION, 3)) {
                    $error = 'Failed to set protocol version to 3.';
                    error_log("\n" . $error);
                    debug_log("(ldapManager->GetUser()) ".$error);
                } else {
                    debug_log("(ldapManager->GetUser()) username: '" . $this->Username . "', Password: '".  $this->password . "'");
                    if(empty($this->Username) || empty($this->password))
                    {
                        echo "ERROR: LDAP Username or LDAP Password not configured correctly";
                        exit();
                    }

                    $bind = $this->Bind($this->Username, $this->password);
                    //$bind = @ldap_bind($ds);
                    //Check to make sure we're bound.
                    if (!$bind) {
                        $error = 'Binding to LDAP failed.';
                        echo "\nERROR: " . $error;
                        error_log($error);
                        debug_log("(ldapManager->GetUser()) ".$error);
                        //exit();
                    } else {
                        //$query = str_replace("{id}", $uid, $this->Query);

                        //debug_log("(ldapManager->GetUser()) Searching for user ID '$uid' (LDAP query: '$query')");
                        $search = $this->Search($baseDN, $query,$attributes);
                        if (isset($search) && !empty($search))
                        {
                            $info = $this->GetSearchResults($search);

                            $this->Close();
                            // TODO: Is this code assuming that $info only contains one record?
                            return $info;
                        }
                        debug_log("(ldapManager->GetUser()) User not found!");
                    }
                    $this->Close();
                }
            }
        }
        catch (Exception $e)
        {
            $err_msg = "An LDAP error occurred while talking to '" . $this->Uri . "': " . $e->getMessage();
            error_log($err_msg);
            debug_log("(ldapManager->GetUser()) ".$err_msg);
        }
        return false;
    }


}
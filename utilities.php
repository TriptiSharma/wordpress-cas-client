<?php
/**
 * Created by JetBrains PhpStorm.
 * User: shawn.south@bellevuecollege.edu
 * Date: 9/25/13
 * Time: 4:40 PM
 * To change this template use File | Settings | File Templates.
 */

// Disable to turn off debug logging
define("ENABLE_DEBUG_LOG", true);
define("DEBUG_LOG_PATH", "/var/tmp");
define ("DEBUG_LOG_FILE", "wordpress-cas-client-debug.log");

/**
 * @param $haystack
 * @param $needle
 *
 * @return bool
 */
function str_starts_with($haystack, $needle)
{
  $idx = stripos($haystack, $needle);

  return (false !== $idx && 0 == $idx);
}

/**
 * @param $message
 */
function debug_log($message)
{
  if (ENABLE_DEBUG_LOG)
  {
    $date = new DateTime();
    if (!error_log("[".$date->format('Y-m-d H:i:s')."] ".$message . "\n", 3, DEBUG_LOG_PATH."/".site_name().DEBUG_LOG_FILE))
    {
      error_log("UNABLE TO WRITE TO DEBUG LOG (" . DEBUG_LOG_PATH . "): '" . $message . "'");
    }
  }
}

function site_name()
{
  if (is_multisite())
  {
    $current_site = get_current_site();
    return trim($current_site->path, "/");
  }
  else
  {
    // TODO: Identify the current blog by some string
    return "";
  }
}

function class_autoloader($class)
{
  $classFile = dirname(__FILE__) . "/" . $class . ".php";
  safe_include_once($classFile);
}

function safe_include_once($file)
{
  debug_log("Including file: '$file'");
  if (file_exists($file))
  {
    /** @noinspection PhpIncludeInspection */
    include_once($file);
  }
  else
  {
    $error = "Failed to load '".$file."' (because it does not exist).";
    debug_log($error);
  }
}

/**
 * Returns a string representing information about the contents of the array.
 * @param $array
 *
 * @return string Count of the values, and contents of the array
 */
function array_info($array)
{
  return "(".array_count_values($array).": ".print_r($array, true);
}

?>
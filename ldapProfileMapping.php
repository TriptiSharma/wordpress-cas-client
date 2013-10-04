<?php
/**
 * Created by JetBrains PhpStorm.
 * User: ssouth
 * Date: 10/3/13
 * Time: 5:39 PM
 * To change this template use File | Settings | File Templates.
 */

class ldapProfileMapping extends profileMapping
{
  public $baseDN = "";
  public $query = "";

  function __construct(string $wpField, string $attribute, string $baseDN = "", string $query = "")
  {
    $this->wpField = $wpField;
    $this->attribute = $attribute;

    if (isset($baseDN))
    {
      $this->baseDN = $baseDN;
    }
    if (isset($query))
    {
      $this->query = $query;
    }
  }
}
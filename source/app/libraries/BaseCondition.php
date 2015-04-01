<?php
class BaseCondition {
  /**
   * @param array $condition
   */
  public function __construct(array $condition = array())
  {
    if (sizeof($condition)) {
      foreach ($condition as $name => $value) {
        if (property_exists($this, $name)) {
          $this->$name = $value;
        } else {
          $message = sprintf('%s property does not exist.', $name);
          throw new InvalidArgumentException($message);
        }
      }
    }
  }

  /**
   * @return array
   */
  public function toArray()
  {
    $class = new ReflectionClass($this);
    $properties = $class->getProperties();

    $array = array();

    foreach ($properties as $property) {
      $array[$property->name] = $this->{$property->name};
    }

    return $array;
  }

  /**
   * @return array
   */
  public function buildQueryString()
  {
    return http_build_query($this->toArray(), '', '&amp;');
  }
}

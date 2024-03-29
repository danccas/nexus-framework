<?php
namespace Core\View;

class Component {
  protected $dom     = null;
  protected $attrs   = [];
  protected $content = null;

  public function render() {

  }
  public function __toString()
  {
      return $this->render();
  }
  public function setContent($cc) {
    $this->content = $cc;
    return $this;
  }
  public function setAttributes($attributes) {
    foreach ($attributes as $attrMatch) {
        if(strpos($attrMatch['key'], ':') === 0) {
          $attr = trim($attrMatch['key'], ':');
          $this->setInt($attr, $attrMatch['val']);
        } else {
          $this->setAttr($attrMatch['key'], $attrMatch['val']);
        }
      }
  }
  public function setInt($name, $value) {
    $this->{$name} = $value;
    return $this;
  }
  public function setAttr($name, $value) {
    if(strpos($name, ':json') !== false) {
      $this->attrs[$name] = $value;
      $value = static::fixJSON($value);
      $value = preg_replace('/(\w+):/i', '"\1":', $value);
      $this->attrs[str_replace(':json', '', $name)] = json_decode($value, true);
    } else {
      if($value === 'true') {
        $value = true;
      } elseif($value === 'false') {
        $value = false;
      }
      $this->attrs[$name] = $value;
    }
    return $this;
  }
  public function attr($name) {
    return $this->attrs[$name];
  }
  public function attrs() {
    return $this->attrs;
  }
  public function setDom($name) {
    $this->dom = $name;
    return $this;
  }
  private static function fixJSON($json) {
        $newJSON = '';

        $jsonLength = strlen($json);
        for ($i = 0; $i < $jsonLength; $i++) {
            if ($json[$i] == '"' || $json[$i] == "'") {
                $nextQuote = strpos($json, $json[$i], $i + 1);
                $quoteContent = substr($json, $i + 1, $nextQuote - $i - 1);
                $newJSON .= '"' . str_replace('"', "'", $quoteContent) . '"';
                $i = $nextQuote;
            } else {
                $newJSON .= $json[$i];
            }
        }

        return $newJSON;
    }
}

<?php
namespace App\Libraries\Condition;

class BaseCondition {
    /**
     * @param array $condition
     */
    public function __construct(array $condition = [])
    {
        if (sizeof($condition)) {
            foreach ($condition as $name => $value) {
                if (property_exists($this, $name)) {
                    $this->$name = $value;
                } else {
                    $message = sprintf('%s property does not exist.', $name);
                    throw new \InvalidArgumentException($message);
                }
            }
        }
    }

    /**
     * @return array
     */
    public function toArray()
    {
        $class = new \ReflectionClass($this);
        $properties = $class->getProperties();

        $array = [];

        foreach ($properties as $property) {
            $array[$property->name] = $this->{$property->name};
        }

        return $array;
    }

    /**
     * 画面間の引き継ぎ用にクエリ文字列を組み立てる。
     *
     * 区切りは URL そのものの文字である '&' にする。戻り値はリンクの URL と
     * して使われ、HTML への逃がしは Html::link 側が行うため、ここで '&amp;'
     * を入れると二重になり、2 つ目以降のパラメータ名が amp;xxx になって
     * 読み捨てられる。
     *
     * @return string
     */
    public function buildQueryString()
    {
        return http_build_query($this->toArray(), '', '&');
    }
}

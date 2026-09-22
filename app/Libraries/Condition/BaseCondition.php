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
                    $this->$name = $this->normalize($name, $value);
                } else {
                    $message = sprintf('%s property does not exist.', $name);
                    throw new \InvalidArgumentException($message);
                }
            }
        }
    }

    /**
     * 受け取った値を、その項目が想定している形へ倒す。
     *
     * 検索条件はクエリ文字列から来るので、形は送る側が決められる。
     * ?location[]=x のように配列を送ると strlen が、?date_month[]=x なら
     * strtotime が TypeError を投げ、絞り込みが 1 つ効かないでは済まずに
     * 画面ごと 500 になっていた。逆に ?activity_category_item_id=1 のように
     * 配列を待っているところへ素の値も来る。
     *
     * 想定と違う形は、その項目の既定値へ倒す。配列を待つ項目に素の値が来た
     * ときだけは、1 件の指定として受ける。画面のリンクが 1 件だけ渡すことが
     * あるため。
     *
     * 判定は宣言時の既定値で行う。型宣言を足して回るより、既に書いてある
     * ものを読むほうが、項目を増やしたときに書き忘れない。
     *
     * @param string $name
     * @param mixed $value
     * @return mixed
     */
    private function normalize($name, $value)
    {
        $default = (new \ReflectionClass($this))->getDefaultProperties()[$name] ?? null;

        if (is_array($default)) {
            if ($value === null) {
                return $default;
            }

            return is_array($value) ? array_values($value) : [$value];
        }

        return is_array($value) ? $default : $value;
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

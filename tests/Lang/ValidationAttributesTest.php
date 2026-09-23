<?php
namespace Tests\Lang;

use Lang;
use ReflectionClass;
use Validator;

use App\Models;
use Tests\TestCase;

/**
 * 検証メッセージに出る項目名を固定する。
 *
 * 過去に 3 通りの壊れ方をした。
 *
 * group から item への改名で列名は変わったが lang/ja/validation.php は
 * group_name のまま残り、Activity::validateVariableFields が渡す
 * Lang::get('validation.attributes.item_name') が未定義になって、
 * 「validation.attributes.item_nameは必須です。」が画面に出ていた。
 *
 * activity_category_item_id は初めから定義が無く、Laravel の既定の
 * 見出し化で「activity category item idは必須です。」と英語が出ていた。
 *
 * 残りは「科目」という、画面のどこにも無い呼び方だった。エラーは入力欄の
 * 近くに出るので、ここだけ別の言葉にすると、どの欄を指しているのか読めない。
 *
 * どれもコメントでは守られない。訳の抜けは下の網羅で、言葉のずれは
 * 期待値の直接検査で止める。
 */
class ValidationAttributesTest extends TestCase {
    /**
     * 画面のラベルと、そこに出るべき項目名。
     *
     * 対応するフォームは settings/activity_category、
     * settings/activity_category_item、cost/variable、dashboard。
     */
    private const EXPECTED = [
        'activity_date' => '発生日',
        'activity_category_id' => '大項目',
        'category_name' => '大項目名',
        'activity_category_item_id' => '小項目',
        'item_name' => '小項目名',
        'cost_type' => '変動・固定',
        'balance_type' => '収支タイプ',
        'amount' => '金額',
        'location' => '場所',
        'content' => '用途',
        'nickname' => '名前',
        'email' => 'メールアドレス',
        'password' => 'パスワード',
        'current_password' => '現在のパスワード',
        'contact_name' => 'お名前',
        'contact_type' => 'お問い合わせ種別',
        'contact_message' => 'メッセージ'
    ];

    /**
     * 検証ルールを持つモデル。$rules は protected なので反射で読む。
     */
    private const MODELS = [
        Models\Activity::class,
        Models\ActivityCategory::class,
        Models\ActivityCategoryItem::class,
        Models\User::class
    ];

    /**
     * 画面のラベルと同じ言葉であること。
     */
    public function testAttributesAreNamedAsTheScreensAre()
    {
        foreach (self::EXPECTED as $attribute => $expected) {
            $this->assertSame(
                $expected,
                Lang::get('validation.attributes.' . $attribute),
                $attribute . ' の項目名が画面のラベルと違う'
            );
        }
    }

    /**
     * モデルの検証ルールに出てくるキーには、必ず訳があること。
     *
     * 列を改名したときにここが落ちる。訳が無いと Laravel はキーを英語へ
     * 見出し化してそのまま画面へ出すので、気付けるのは利用者になる。
     */
    public function testEveryRuleKeyHasATranslation()
    {
        foreach (self::MODELS as $class) {
            $property = (new ReflectionClass($class))->getProperty('rules');
            $property->setAccessible(true);

            foreach (array_keys($property->getValue(new $class)) as $attribute) {
                $this->assertArrayHasKey(
                    $attribute,
                    self::EXPECTED,
                    $class . ' の ' . $attribute . ' に対応する項目名が無い'
                );
            }
        }
    }

    /**
     * 組み上がったメッセージに、翻訳キーそのものや英語が出ないこと。
     *
     * 訳が定義されていても、Lang::get の戻り値を名前として渡す経路では
     * キー名がそのまま出る。実際に Validator に通したメッセージで見る。
     */
    public function testMessagesCarryNoRawKeyOrEnglish()
    {
        foreach (self::EXPECTED as $attribute => $expected) {
            $validator = Validator::make([], [$attribute => 'required']);
            $validator->fails();

            $message = $validator->messages()->first($attribute);

            $this->assertSame($expected . 'は必須です。', $message, $attribute . ' のメッセージが崩れている');
            $this->assertStringNotContainsString('validation.attributes.', $message);
            $this->assertDoesNotMatchRegularExpression('/[a-z]{2,}/', $message, $attribute . ' に英語が残っている');
        }
    }
}

<?php
namespace Tests\Services;

use App\Services\ContactService;
use Tests\TestCase;

class ContactServiceTest extends TestCase {
    private $contact;

    protected function setUp(): void
    {
        parent::setUp();

        $this->contact = new ContactService;
    }

    public function testGetContactTypeList()
    {
        $this->assertEquals(gettype($this->contact->getContactTypeList()), 'array');
    }

    public function testSend()
    {
        $params = [
            'contact_name' => 'test',
            'email' => 'test@monelytics.me',
            'contact_type' => 1,
            'contact_message' => 'test'
        ];
        $user_id = null;
        $this->assertEquals($this->contact->send($params, $user_id), true);

        $user_id = 1;
        $this->assertEquals($this->contact->send($params, $user_id), true);
    }

    /**
     * 一覧に無い種別は弾く。以前は required だけで、範囲外の値がそのまま
     * $contact_type_list[...] の未定義キーになり 500 になっていた。この画面は
     * 未認証で開けるため、誰でも踏める状態だった。
     */
    public function testSendRejectsUnknownContactType()
    {
        foreach (['9', 'x', ['1'], ''] as $value) {
            $errors = [];
            $params = [
                'contact_name' => 'n',
                'email' => 'a@example.test',
                'contact_type' => $value,
                'contact_message' => 'm'
            ];

            $this->assertFalse(
                $this->contact->send($params, null, $errors),
                sprintf('%s が通ってしまった', var_export($value, true))
            );
            $this->assertArrayHasKey('contact_type', $errors);
        }
    }

    /**
     * 画面の選択肢は今までどおり通る。絞りすぎていないこと。
     */
    public function testSendAcceptsEveryListedContactType()
    {
        foreach (array_filter(array_keys($this->contact->getContactTypeList()), 'strlen') as $value) {
            $errors = [];
            $params = [
                'contact_name' => 'n',
                'email' => 'a@example.test',
                'contact_type' => $value,
                'contact_message' => 'm'
            ];

            $this->assertTrue(
                $this->contact->send($params, null, $errors),
                sprintf('%s が弾かれた: %s', $value, var_export($errors, true))
            );
        }
    }
}

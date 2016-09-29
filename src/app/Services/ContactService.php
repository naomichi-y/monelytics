<?php
namespace App\Services;

use Config;
use Mail;
use Validator;

class ContactService
{
    /**
     * * お問い合わせ種別のリストを取得する。
     *
     * @return array
     */
    public function getContactTypeList()
    {
        $array = [
            '' => '種別の指定',
            '1' => 'サービスへの質問',
            '2' => 'サービスへの要望',
            '3' => '不具合の報告',
            '4' => 'その他'
        ];

        return $array;
    }

    /**
     * お問い合わせ内容を管理者へ送信する。
     *
     * @param array $fields
     * @param int $user_id
     * @param array &$errors
     * @return bool
     */
    public function send($fields, $user_id = null, &$errors = [])
    {
        $rules = [
            'contact_name' => 'required',
            'email' => 'required|email',
            'contact_type' => 'required',
            'contact_message' => 'required'
        ];

        $validator = Validator::make($fields, $rules);
        $result = false;

        if ($validator->fails()) {
            $errors = $validator->messages()->toArray();

        } else {
            $contact_type_list = $this->getContactTypeList();

            $data = $fields;
            $data['user_id'] = $user_id;
            $data['contact_type'] = $contact_type_list[$data['contact_type']];

            Mail::send(['text' => 'emails/contact'], $data, function($message) use ($data) {
                $subject = '[CONTACT] ' . $data['contact_type'];
                $message->to(Config::get('app.notice.contact'))
                    ->subject($subject);
            });

            $result = true;
        }

        return $result;
    }
}

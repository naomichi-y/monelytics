<?php

return array(

  /*
  |--------------------------------------------------------------------------
  | Validation Language Lines
  |--------------------------------------------------------------------------
  |
  | The following language lines contain the default error messages used by
  | the validator class. Some of these rules have multiple versions such
  | such as the size rules. Feel free to tweak each of these messages.
  |
  */

  "accepted"         => ":attributeが確認されていません。",
  "active_url"       => ":attributeは無効なURLです。",
  "after"            => ":attributeは:dateより後の日付でなければなりません。",
  "alpha"            => ":attributeにはアルファベット以外使用できません。",
  "alpha_dash"       => ":attributeにはアルファベット、数字、ハイフン、アンダーバー以外使用できません。",
  "alpha_num"        => ":attributeにはアルファベット、数字以外使用できません。",
  "before"           => ":attributeは:dateより前の日付でなければなりません。",
  "between"          => array(
    "numeric" => ":attributeは:min～:maxの範囲である必要があります。",
    "file"    => ":attributeのファイルサイズは:min～:maxキロバイトの範囲である必要があります。",
    "string"  => ":attributeの長さは:min～:max文字の範囲である必要があります。",
  ),
  "confirmed"        => ":attributeは確認欄と一致しませんでした。",
  "date"             => ":attributeは正しい日付ではありません。",
  "date_format"      => ":attributeは:format形式ではありません。",
  "different"        => ":attributeと:otherは異なる必要があります。",
  "digits"           => ":attributeは:digits桁である必要があります。",
  "digits_between"   => ":attributeは:min～:max桁の範囲である必要があります。",
  "email"            => ":attributeは正しいメールアドレスではありません。",
  "exists"           => "選択された:attributeは存在しませんでした。",
  "not_exists"       => "選択された:attributeは使用できません。",
  "image"            => ":attributeは画像ファイルである必要があります。",
  "in"               => "選択された:attributeは正しくありません。",
  "integer"          => ":attributeは整数である必要があります。",
  "ip"               => ":attributeは正しいIPアドレスではありません。",
  "max"              => array(
    "numeric" => ":attributeは:max以下である必要があります。",
    "file"    => ":attributeのファイルサイズは:maxキロバイト以下である必要があります。",
    "string"  => ":attributeの長さは:max文字以下である必要があります。",
  ),
  "mimes"            => ":attributeのファイル種別は:valuesである必要があります。",
  "min"              => array(
    "numeric" => ":attributeは:min以上である必要があります。",
    "file"    => ":attributeのファイルサイズは:minキロバイト以上である必要があります。",
    "string"  => ":attributeの長さは :min文字以上である必要があります。",
  ),
  "not_in"           => "選択された:attributeは正しくありません。",
  "numeric"          => ":attributeは数値である必要があります。",
  "regex"            => ":attributeの形式は正しくありません。",
  "required"         => ":attributeは必須です。",
  "required_if"      => ":otherが:valueである場合、:attributeは必須です。",
  "required_with"    => ":valuesが指定されている場合、:attributeは必須です。",
  "required_without" => ":valuesが指定されていない場合、:attributeは必須です。",
  "same"             => ":attributeと:otherが一致しません。",
  "size"             => array(
    "numeric" => ":attributeは:sizeである必要があります。",
    "file"    => ":attributeのファイルサイズは:sizeキロバイトである必要があります。",
    "string"  => ":attributeの長さは:size文字である必要があります。",
  ),
  "unique"           => ":attributeはすでに使われています。",
  "url"              => ":attributeは正しいURL形式ではありません。",

  /*
  |--------------------------------------------------------------------------
  | Custom Validation Language Lines
  |--------------------------------------------------------------------------
  |
  | Here you may specify custom validation messages for attributes using the
  | convention "attribute.rule" to name the lines. This makes it quick to
  | specify a specific custom language line for a given attribute rule.
  |
  */

  'custom' => array(
    'user' => array(
      'login' => array(
        'authentication' => 'ログインに失敗しました。'
      ),
      'createWithOAuth' => array(
        'registration' => '既に会員登録が完了しています。'
      ),
    ),
    'create_record_none' => '登録対象データがありません。',
    'create_success' => '登録が完了しました。',
    'update_success' => '更新が完了しました。',
    'delete_success' => '削除が完了しました。',
    'send_success' => 'お問い合わせありがとうございました。 内容を確認次第、ご返信させて頂きます。'
  ),

  /*
  |--------------------------------------------------------------------------
  | Custom Validation Attributes
  |--------------------------------------------------------------------------
  |
  | The following language lines are used to swap attribute place-holders
  | with something more reader friendly such as E-Mail Address instead
  | of "email". This simply helps us make messages a little cleaner.
  |
  */

  'attributes' => array(
    'nickname' => '名前',
    'email' => 'メールアドレス',
    'password' => 'パスワード',
    'activity_date' => '発生日',
    'activity_category_id' => '科目カテゴリ名',
    'category_name' => '科目カテゴリ名',
    'group_name' => '科目名',
    'amount' => '金額',
    'location' => '場所',
    'content' => '用途',
    'cost_type' => '科目タイプ',
    'balance_type' => '収支タイプ',
    'contact_name' => 'お名前',
    'contact_type' => 'お問い合わせ種別',
    'contact_message' => 'メッセージ'
  ),

);

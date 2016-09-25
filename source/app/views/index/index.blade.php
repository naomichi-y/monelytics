@extends('layouts.parallax')

@section('content')
  <div class="parallax parallax_view_1" data-parallax="scroll" data-bleed="60" data-speed="0.2" data-image-src="/assets/images/parallax/01.jpg">
    <div class="lead">
      <h2>The Ants and the Grasshopper</h2>
      <p>アリとキリギリス</p>
    </div>
  </div>

  <div class="parallax parallax_view_2" data-parallax="scroll" data-speed="0.4" data-image-src="/assets/images/parallax/02.jpg">
    <div class="lead">
      <h2>monelytics is money + analytics</h2>
      <p>
        「お金」の使い方を「分析」する。<br />
        monelyticsはシンプルで使いやすい無料の家計簿アプリです。
      </p>
    </div>
  </div>

  <div class="parallax parallax_view_3" data-parallax="scroll" data-speed="0.6">
    <div class="lead">
      <div class="row">
        <div class="col-md-6">
          <h3>使いやすさ</h3>
          <p>
            家計簿は毎日入力することが大切。monelyticsは誰もが使いやすいサービスを目指しました。
          </p>
        </div>
        <div class="col-md-6 text-center">
          {{HTML::image('assets/images/overview/01.png', '', array('class' => 'overview_fig'))}}
        </div>
      </div>

      <div class="row">
        <div class="col-md-6 float_right">
          <h3>シンプルな機能</h3>
          <p>
            提供する機能は、収支管理・レポート管理・科目管理といたってシンプル。余計な機能は付けず、シンプルで分かりやすい構成としました。<br />
            もちろん、全ての機能は無料でご利用いただけます。
          </p>
        </div>
        <div class="col-md-6 text-center">
          {{HTML::image('assets/images/overview/02.png', '', array('class' => 'overview_fig'))}}
        </div>
      </div>

      <div class="row">
        <div class="col-md-6">
          <h3>REST APIの提供</h3>
          <p>
            将来的にご登録いただいたデータをREST APIで提供する予定があります。<br />
            これにより様々なWebサービスとデータを連携することが可能となります。
          </p>
        </div>
        <div class="col-md-6 text-center">
          {{HTML::image('assets/images/overview/03.png', '', array('class' => 'overview_fig'))}}
        </div>
      </div>
    </div>
  </div>

  <div class="parallax parallax_view_4" data-parallax="scroll" data-bleed="20" data-speed="0.8" data-image-src="/assets/images/parallax/04.jpg">
    <div class="lead">
      <h2>新しい生活を始めよう</h2>
      <div class="start">{{link_to('user/create', '会員登録')}}</div>
    </div>
  </div>
@stop

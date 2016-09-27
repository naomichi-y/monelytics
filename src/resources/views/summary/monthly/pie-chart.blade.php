<script>
$(function () {
  $("#balance_type_{{Input::get('balance_type')}}").loadPieChart({
    balance_type: {!! Html::encodeJsJsonValue('balance_type') !!},
    date_month: {!! Html::encodeJsJsonValue('date_month') !!},
    begin_date: {!! Html::encodeJsJsonValue('begin_date') !!},
    end_date: {!! Html::encodeJsJsonValue('end_date') !!},
  });
});
</script>
<div id="balance_type_{{Input::get('balance_type')}}"></div>

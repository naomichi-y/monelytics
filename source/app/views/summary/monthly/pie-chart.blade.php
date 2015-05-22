<script>
$(function () {
  $("#balance_type_{{Input::get('balance_type')}}").loadPieChart({
    balance_type: {{HTML::encodeJsJsonValue('balance_type')}},
    date_month: {{HTML::encodeJsJsonValue('date_month')}},
    begin_date: {{HTML::encodeJsJsonValue('begin_date')}},
    end_date: {{HTML::encodeJsJsonValue('end_date')}},
  });
});
</script>
<div id="balance_type_{{Input::get('balance_type')}}"></div>

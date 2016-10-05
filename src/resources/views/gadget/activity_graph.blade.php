<style>
    .svg_container {
        border: 1px solid #BBBBBB;
        padding: 2px;
    }

    /**
     * heat-level-0: 収支が0
     */
    .heat-level-0 {
        fill: #f4f4f4;
    }

    /**
     * heat-level-1: 収入が多い (色が濃い)
     * heat-level-6: 収入が少ない (色が薄い)
     */
    .heat-level-1 {
        fill: #269ef0;
    }

    .heat-level-2 {
        fill: #46acf2;
    }

    .heat-level-3 {
        fill: #66baf4;
    }

    .heat-level-4 {
        fill: #86c8f6;
    }

    .heat-level-5 {
        fill: #a5d7f9;
    }

    .heat-level-6 {
        fill: #c5e5fb;
    }

    /**
     * heat-level-7: 支出が少ない (色が薄い)
     * heat-level-12: 支出が多い (色が濃い)
     */
    .heat-level-7 {
        fill: #fad6d2;
    }

    .heat-level-8 {
        fill: #f7bab4;
    }

    .heat-level-9 {
        fill: #f49e95;
    }

    .heat-level-10 {
        fill: #f18176;
    }

    .heat-level-11 {
        fill: #f07366;
    }

    .heat-level-12 {
        fill: #ec5748;
    }

    .header {
        fill: #808080;
        font-size: 0.7em;
        text-anchor: middle;
    }

    .rect, .balloon {
        cursor: pointer;
    }
</style>
<script>
    $(function() {
        $(".rect").darkTooltip({
            animation: "fadeIn"
        });

        $(document).on("click", ".rect", function() {
            redirect($(this));
        });

        $(document).on("click touchstart", ".balloon", function() {
            redirect($(this));
        });

        function redirect(target) {
            var date = target.attr("data-date");
            location.href = "/summary/daily?begin_date=" + date + "&end_date=" + date;
        }
    });
</script>

<svg width="{{$svg_width}}" height="{{$svg_height}}" class="svg_container">
    <?php $y = $data_start_y; ?>
    @foreach ($days as $day)
        <text x="10" y="{{$y + 15}}" class="header">{{$day}}</text>
        <?php $y = $y + $rect_size + $padding_size; ?>
    @endforeach

    <?php $x = $data_start_x; ?>
    @foreach ($latest_activities as $week_id => $summary)
        <?php $y = $data_start_y; ?>
        <g>
            @foreach ($summary as $date => $data)
                <rect x="{{$x}}" y="{{$y}}" width="{{$rect_size}}" height="{{$rect_size}}" class="heat-level-{{$data['heat_level']}} rect" data-date="{{$date}}" data-amount="{{$data['amount']}}" data-tooltip="<div class='balloon' data-date='{{$date}}'>発生日: {{str_replace('-', '/', $date)}}<br />金額: {{number_format($data['amount'])}}</div>" />
                <?php $y = $y + $rect_size + $padding_size; ?>
            @endforeach
        </g>
        <?php $x = $x + $rect_size + $padding_size; ?>
    @endforeach
</svg>


<?php

function getFragments($mem) {
	$frags = array();
	foreach($mem['block_lists'][0] as $block){
		$frags[$block['offset']] = $block['size'];
	}
	ksort($frags);
	
	$text = array();
	$prev_val = 0;
	$i = 0;
	foreach($frags as $key=>$value){
		$i++;
		$key = $key - $prev_val;
		$text[] = "{name: 'Used-".bsize($key)."',data: [".($key)."]}";
		$text[] = "{name: 'Free-".bsize($value)."',data: [".($value)."]}";
		$prev_val = $key + $prev_val + $value;
	}
	return implode(',',array_reverse($text));
}



?>
<style>
    .memblock{
        height: 20px;
        width:20px;
        float: left;
        border: 1px solid cyan;
        cursor: pointer;
    }
    .memblock:hover{
        border: 1px solid blue !important;
    }
</style>
<script>
    $(function () {
    
        Highcharts.setOptions({
             colors: [ '#50B432','#ED561B']
        });
    
        $('#frag_container').highcharts({
            chart: {
                type: 'column',
                zoomType:'y'
            },
            title: {
                text: ''
            },
            xAxis: {
                categories: ['Block 1']
            },
            yAxis: {
                min: 0,
                title: {
                    text: 'Memory Fragmentaions   (Bytes)'
                },
                stackLabels: {
                    enabled: true,
                    style: {
                        fontWeight: 'bold',
                        color: (Highcharts.theme && Highcharts.theme.textColor) || 'gray'
                    }
                }
            },
            tooltip: {
                formatter: function() {
                    return '<b>'+ this.series.name+'</b>'
                }
            },
            plotOptions: {
                column: {
                    stacking: 'normal',
                    dataLabels: {
                        enabled: true,
                        color: (Highcharts.theme && Highcharts.theme.dataLabelsColor) || 'white',
                        style: {
                            textShadow: '0 0 3px black, 0 0 3px black'
                        }
                    }
                }
            },
            series: [<?php echo getFragments($mem); ?>],
            credits: {
                enabled: false
            },
            legend : {
                enabled: false
            }
        });
    });
</script>
<div class="col-md-3" style="padding-left: 0px">
    <div id="frag_container" style="width: 250px;height: 100%"></div>
</div>
<div class="col-md-9" style="padding-left: 0px">
    
    <ul class="nav nav-tabs">
        <li class="active"><a href="#fragmentation-opcode" data-toggle="tab">Memory Segments - Opcode Cache</a></li>
        <li><a href="#fragmentation-user" data-toggle="tab">Memory Segments - User Cache</a></li>
    </ul>
    
    <div class="tab-content" style="padding-top: 20px">
        <div class="tab-pane active" id="fragmentation-opcode">
            <h3>Memory Segments - Opcode Cache</h3>
            <?php
                        
            $slot = $cache['slot_distribution'];
                        
            echo'<div>';
            foreach($slot as $key=>$value){
                if($value==1){
                    echo "<div class='memblock bg-primary'></div>";
                }else{
                    echo "<div class='memblock $key'></div>";
                }
            }
            echo '</div>';
            ?>
        </div>
        
        <div class="tab-pane" id="fragmentation-user">
            <h3>Memory Segments - User Cache</h3>
            <?php
            
            $slot = apc_cache_info('user');
            $slot = $slot['slot_distribution'];
            
            echo'<div>';
            foreach($slot as $key=>$value){
                if($value==1){
                    echo "<div class='memblock bg-primary'></div>";
                }else{
                    echo "<div class='memblock $key'></div>";
                }
            }
            echo '</div>';
            ?>
        </div>
    </div>
    
</div>
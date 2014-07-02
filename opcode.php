<?php

$hits       = array();
$missess    =  array();
for($i=20; $i>=0; $i--){
   $hits[] = "{x:ctime-".($i*1000).",y:".intval($cache['num_hits'])."}";
   $missess[] = "{x:ctime-".($i*1000).",y:".intval($cache['num_misses'])."}";
}

?>
<script>
    
    
    $(document).ready(function(){
        
        
        
        var chart_options = {
            chart: {
                type: 'line',
                renderTo: 'opcode_progress',
                animation: {
                    duration : 980,
                    easing : 'linear'
                },
                events: {
                    load: function() {
                        opcode_progress[0] = this.series[0];
                        opcode_progress[1] = this.series[1];
                    }
                }
            },
            title: {
                text: ''
            },
            subtitle: {
                text: 'Cached files Hits & Missess'
            },
            xAxis: {
                type: 'datetime',
                tickPixelInterval: 150
            },
            yAxis: {
                min : 0,
                title: {
                    text: ''
                },
                plotLines: [{
                    value: 0,
                    width: 1,
                    color: '#808080'
                }]
            },
            tooltip: {
                shared: true
            },
            plotOptions: {
                area: {
                    stacking: 'normal',
                    lineColor: '#666666',
                    lineWidth: 1,
                    marker: {
                        lineWidth: 1,
                        lineColor: '#666666'
                    }
                }
            },
            series: [{
                name: 'Hits',
                data: [<?php echo implode(',',$hits)?>]
            }, {
                name: 'Miss',
                data: [<?php echo implode(',',$missess)?>]
            }],
            credits: {
                enabled: false
            }
        };
        opcode_chart = new Highcharts.Chart(chart_options);
        
        $("[ajax]").click(function(){
            $(this).attr('disabled','disabled');
            var value  = $(this).attr("ajax");
            var clicked_btn = $(this);
            var btn_text = $(this).html();
            $.getJSON('apc.php?'+value,function(response){
                console.log(response);
                if(response.status==1){
                    clicked_btn.html(response.text);
                    setInterval(function(){clicked_btn.html(btn_text);clicked_btn.removeAttr('disabled');},5000);
                }else{
                    clicked_btn.html(response.text);
                    clicked_btn.removeAttr('disabled');
                }
            });
        });
        
        $(".refresh_cache_file,.refresh_cache_folder").click(function(){
            $(this).attr('disabled','disabled');
            var value  = $(this).attr("ajaxr");
            var clicked_btn = $(this);
            var btn_text = $(this).html();
            $(this).html('<img src="./img/489.GIF"/>');
            $.getJSON('apc.php?'+value,function(response){
                if(response.status==1){
                    clicked_btn.html('<span class="glyphicon glyphicon-ok-circle"></span>');
                    setInterval(function(){clicked_btn.html(btn_text);clicked_btn.removeAttr('disabled');},5000);
                }else{
                    clicked_btn.html('<span class="glyphicon glyphicon-remove-circle"></span>');
                    setInterval(function(){clicked_btn.html(btn_text);clicked_btn.removeAttr('disabled');},5000);
                }
            });
        });
        
        
        $(".remove_cache_file,.remove_cache_folder,.remove_cache_user").click(function(){
            $(this).attr('disabled','disabled');
            var value  = $(this).attr("ajaxr");
            var clicked_btn = $(this);
            var btn_text = $(this).html();
            $(this).html('<img src="./img/489.GIF"/>');
            $.getJSON('apc.php?'+value,function(response){
                if(response.status==1){
                    clicked_btn.html('<span class="glyphicon glyphicon-ok-circle"></span>');
                    setInterval(function(){
                        clicked_btn.html(btn_text);
                        clicked_btn.removeAttr('disabled');
                        clicked_btn.parents('tr').remove();
                    },3000);
                }else{
                    clicked_btn.html('<span class="glyphicon glyphicon-remove-circle"></span>');
                    setInterval(function(){clicked_btn.html(btn_text);clicked_btn.removeAttr('disabled');},5000);
                }
            });
        });
        
        
        $("#add_directory").click(function(){
            var dir = $("[name='add_directory']").val();
            $(this).attr('disabled','disabled');
        })
        
        
        
    })
    
    
</script>
<style>
    .sorter th:hover{
        text-decoration: underline;
    }
    .sorter th{
        cursor: pointer;
    }
    td.options .btn{
        padding: 4px 6px;
    }
    td.options{
        padding: 3px !important;
    }
    .bs-callout-info {
        background-color: #F0F7FD;
        border-color: #D0E3F0;
    }
    .bs-callout {
        margin: 0px;
        padding: 10px;
        border-left: 6px solid #ADDBFF;
    }
</style>

<div class="tab-pane" id="opcode">
    <div class="col-md-12" style="padding-left: 0px">
        
        <div class="panel panel-info">
            <div class="panel-heading">
                <h3 class="panel-title">
                    File Cache Information
                    <button type="button" class="btn btn-primary" ajax="clear=opcode" style="float: right;width: 160px;top: -8px;position: relative">Clear Opcode Cache</button>
                </h3>
            </div>
            <div class="panel-body" style="padding: 0px">
                
                <div class="col-md-6">
                    <div id="opcode_progress" style="width:550px;overflow: hidden"></div>
                </div>
                
                <div class="col-md-6">
                    <h4>Opcode Cache Information</h4>
                    <table cellspacing=0 class="table table-striped">
                        <tbody>
                            <tr class=tr-0><td class=td-0>Cached Files</td><td><?php echo $number_files;?> (<?php echo $size_files?>)</td></tr>
                            <tr class=tr-1><td class=td-0>Hits</td><td><?php echo $cache['num_hits']?></td></tr>
                            <tr class=tr-0><td class=td-0>Misses</td><td><?php echo $cache['num_misses']?></td></tr>
                            <tr class=tr-1><td class=td-0>Request Rate (hits, misses)</td><td><?php echo $req_rate?> cache requests/second</td></tr>
                            <tr class=tr-0><td class=td-0>Hit Rate</td><td><?php echo $hit_rate?> cache requests/second</td></tr>
                            <tr class=tr-1><td class=td-0>Miss Rate</td><td><?php echo $miss_rate?> cache requests/second</td></tr>
                            <tr class=tr-0><td class=td-0>Insert Rate</td><td><?php echo $insert_rate?> cache requests/second</td></tr>
                            <tr class=tr-1><td class=td-0>Cache full count</td><td><?php echo $cache['expunges']?></td></tr>
                        </tbody>
                    </table>
                </div>
                
            </div>
        </div>
        
    </div>
</div>

<div class="tab-pane" id="per-directory">
    <div class="col-md-12" style="padding-left: 0px">
        
        <div class="panel panel-info" style="clear: both">
            <div class="panel-heading">
                <h3 class="panel-title">File Cache For Each Directories</h3>
            </div>
            <div class="panel-body" style="padding: 0px">
                
                <div class="bs-callout bs-callout-info" style="overflow: hidden">
                    <div class="col-md-4" style="padding-left: 0px">
                        <h5>Add A Directory To Cache</h5>
                    </div>
                    <div class="col-md-8" style="padding-left: 0px">
                        <div class="input-group">
                          <input type="text" class="form-control"  name="add_directory">
                          <span class="input-group-btn">
                            <button class="btn btn-default" type="button" id="add_directory">Add Directory</button>
                          </span>
                        </div><!-- /input-group -->
                    </div>
                </div>
                
                
                <table cellspacing=0 class="table table-striped sorter">
                    <thead>
                        <tr>
                            <th>Directory</th>
                            <th>Number of Files</th>
                            <th>Total Hits</th>
                            <th>Total Size</th>
                            <th>Avg. Hits</th>
                            <th>Avg. Size</th>
                            <th>Options</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $tmp = $list = array();
                        foreach($cache[$scope_list[$MYREQUEST['SCOPE']]] as $entry) {
                            $n = dirname($entry['filename']);
                            if ($MYREQUEST['AGGR'] > 0) {
                                $n = preg_replace("!^(/?(?:[^/\\\\]+[/\\\\]){".($MYREQUEST['AGGR']-1)."}[^/\\\\]*).*!", "$1", $n);
                            }
                            if (!isset($tmp[$n])) {
                                $tmp[$n] = array('hits'=>0,'size'=>0,'ents'=>0);
                            }
                            $tmp[$n]['hits'] += $entry['num_hits'];
                            $tmp[$n]['size'] += $entry['mem_size'];
                            ++$tmp[$n]['ents'];
                        }
                    
                        foreach ($tmp as $k => $v) {
                            switch($MYREQUEST['SORT1']) {
                                case 'A': $kn=sprintf('%015d-',$v['size'] / $v['ents']);break;
                                case 'T': $kn=sprintf('%015d-',$v['ents']);		break;
                                case 'H': $kn=sprintf('%015d-',$v['hits']);		break;
                                case 'Z': $kn=sprintf('%015d-',$v['size']);		break;
                                case 'C': $kn=sprintf('%015d-',$v['hits'] / $v['ents']);break;
                                case 'S': $kn = $k;					break;
                            }
                            $list[$kn.$k] = array($k, $v['ents'], $v['hits'], $v['size']);
                        }
                        
                        if ($list) {
                            $i = 0;
                            foreach($list as $entry) {
                                echo
                                    '<tr>',
                                    "<td>",$entry[0],'</a></td>',
                                    '<td>',$entry[1],'</td>',
                                    '<td>',$entry[2],'</td>',
                                    '<td>',$entry[3],'</td>',
                                    '<td>',round($entry[2] / $entry[1]),'</td>',
                                    '<td>',round($entry[3] / $entry[1]),'</td>'
                                    ;?>
                                    <td class="options">
                                        <button type="button" class="btn btn-info refresh_cache_folder" ajaxr="refresh_cache_folder=<?php echo $entry[0] ?>"><span class="glyphicon glyphicon-refresh"></span></button>
                                        <button type="button" class="btn btn-danger remove_cache_folder" ajaxr="remove_cache_folder=<?php echo $entry[0] ?>"><span class="glyphicon glyphicon-trash"></span></button>
                                    </td>
                                    <?php
                                    echo '</tr>';
                    
                                if (++$i == $MYREQUEST['COUNT']) break;
                            }
                            
                        } else {
                            echo '<tr class=tr-0><td class="center" colspan=6><i>No data</i></td></tr>';
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
        
    </div>
</div>

<div class="tab-pane" id="per-file">
    <div class="col-md-12" style="padding-left: 0px">
        
        <div class="panel panel-info" style="clear: both">
            <div class="panel-heading">
                <h3 class="panel-title">File Cache For Each Files</h3>
            </div>
            <div class="panel-body" style="padding: 0px">
                <table cellspacing=0 class="table table-striped sorter">
                    <thead>
                        <tr>
                            <th>File</td>
                            <th>Hits</td>
                            <th>Size</td>
                            <th>Created</td>
                            <th>Modified</td>
                            <th>Last Accessed</td>
                            <th>Options</td>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        foreach($cache['cache_list'] as $row){
                            if($row['type']=='file'){
                                ?>
                                <tr>
                                    <td><?php echo $row['filename'] ?> </td>
                                    <td><?php echo $row['num_hits']?> </td>
                                    <td><?php echo $row['mem_size']?> </td>
                                    <td>
                                        <span class="timeago" title="<?php echo date('c',$row['creation_time'])?>">
                                            <?php echo date('r ',$row['creation_time'])?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="timeago" title="<?php echo date('c',$row['mtime'])?>">
                                            <?php echo date('r ',$row['mtime'])?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="timeago" title="<?php echo date('c',$row['access_time'])?>">
                                            <?php echo date('r ',$row['access_time'])?>
                                        </span>
                                    </td>
                                    <td class="options">
                                        <button type="button" class="btn btn-info refresh_cache_file" ajaxr="refresh_cache_file=<?php echo $row['filename'] ?>"><span class="glyphicon glyphicon-refresh"></span></button>
                                        <button type="button" class="btn btn-danger remove_cache_file" ajaxr="remove_cache_file=<?php echo $row['filename'] ?>"><span class="glyphicon glyphicon-trash"></span></button>
                                    </td>
                                </tr>
                                <?php
                            }
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
        
    </div>
</div>
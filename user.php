<?php

$hits       = array();
$missess    = array();

for($i=20; $i>=0; $i--){
   $hits[] = "{x:ctime-".($i*1000).",y:".intval($cache_user['num_hits'])."}";
   $missess[] = "{x:ctime-".($i*1000).",y:".intval($cache_user['num_misses'])."}";
}

?>
<style>
    div{
        border-radius:0px !important;
    }
</style>
<script>
    
    
    $(document).ready(function(){
                
        var user_chart_options = {
            chart: {
                type: 'line',
                renderTo: 'user_progress',
                animation: {
                    duration : 1000,
                    easing : 'linear'
                },
                events: {
                    load: function() {
                        user_progress[0] = this.series[0];
                        user_progress[1] = this.series[1];
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
        user_chart = new Highcharts.Chart(user_chart_options);
        
        
        $(".var_details").click(function(){
            var title = $(this).parent().find('.var_details_body').attr('title');
            var body  = $(this).parent().find('.var_details_body').html();
            var ajaxr = $(this).parent().find()
            
            $("#myModal .modal-title").html(title);
            $("#myModal .modal-body").html(body);
            
            $("#myModal").modal('show');
            
        })
        
        
        $("#save_variable").click(function(){
            var name,value,ttl;
            name  = $("#myModal [name='name']").val();
            ttl   = $("#myModal [name='ttl']").val();
            value = '';
            if($("#myModal [name='value']").length>0){
                value  = '&value='+encodeURI($("#myModal [name='value']").val());
            }
            
            $(this).attr('disabled','disabled');
            $(this).html('<img src="./img/489.GIF"/> Please wait');
            $.getJSON('apc.php?setvariable=' + encodeURI(name) + '&ttl=' + encodeURI(ttl) + value,function(data){
                $("#myModal").modal('hide');
                $("#save_variable").removeAttr('disabled');
                $("#save_variable").html('Save changes');
            })
            
        });
        
    });
</script>

<div class="tab-pane" id="user">
    <div class="col-md-12" style="padding-left: 0px">
        
        <div class="panel panel-info">
            <div class="panel-heading">
                <h3 class="panel-title">
                    User Variables Cache Information
                    <button type="button" class="btn btn-primary" ajax="clear=user" style="float: right;width: 160px;top: -8px;position: relative">Clear User Cache</button>
                </h3>
            </div>
            <div class="panel-body" style="padding: 0px">
                
                <div class="col-md-6">
                    <div id="user_progress" style="width:550px;overflow: hidden"></div>
                </div>
                
                <div class="col-md-6">
                    <h4>User Variable Cache Information</h4>
                    <table cellspacing=0 class="table table-striped">
                        <tbody>
                            <tr class=tr-0><td class=td-0>Cached Variables</td><td><?php echo $number_vars?> (<?php echo $size_vars?>)</td></tr>
                            <tr class=tr-1><td class=td-0>Hits</td><td><?php echo $cache_user['num_hits']?></td></tr>
                            <tr class=tr-0><td class=td-0>Misses</td><td><?php echo $cache_user['num_misses']?></td></tr>
                            <tr class=tr-1><td class=td-0>Request Rate (hits, misses)</td><td><?php echo $req_rate_user?> cache requests/second</td></tr>
                            <tr class=tr-0><td class=td-0>Hit Rate</td><td><?php echo $hit_rate_user?> cache requests/second</td></tr>
                            <tr class=tr-1><td class=td-0>Miss Rate</td><td><?php echo $miss_rate_user?> cache requests/second</td></tr>
                            <tr class=tr-0><td class=td-0>Insert Rate</td><td><?php echo $insert_rate_user?> cache requests/second</td></tr>
                            <tr class=tr-1><td class=td-0>Cache full count</td><td><?php echo $cache_user['expunges']?></td></tr>
                        </tbody>
                    </table>
                </div>
                
            </div>
        </div>
        
    </div>
</div>



<div class="tab-pane" id="per-user">
    <div class="col-md-12" style="padding-left: 0px">
        
        <div class="panel panel-info" style="clear: both">
            <div class="panel-heading">
                <h3 class="panel-title">User Variabled in Cache</h3>
            </div>
            <div class="panel-body" style="padding: 0px">
                <?php
                if(count($cache_user['cache_list'])==0){
                    ?>
                    <div class="alert alert-warning fade in">
                        <button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button>
                        Sorry, no user varibles found in opcode cache
                    </div>
                    <?php
                } else {
                ?>
                <table cellspacing=0 class="table table-striped sorter">
                    <thead>
                        <tr>
                            <th>Variable</td>
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
                        foreach($cache_user['cache_list'] as $row){
                            if((isset($row['type']) && $row['type']=='user') || !isset($row['type'])){
                                ?>
                                <tr>
                                    <td>
                                        <span class="var_details" style="cursor: pointer">
                                            <?php echo $row['info'] ?>
                                        </span>
                                        <div style="display: none" class="var_details_body" title="<?php echo $row['info'] ?> Details" ajaxr="remove_cache_user=<?php echo $row['info'] ?>">
                                            <?php
                                            $var  = apc_fetch($row['info']);
                                            $type = gettype($var);
                                            ?>
                                            <input type="hidden" name="name" value="<?php echo $row['info'] ?>"/> 
                                            <table class="table table-striped">
                                                <tr>
                                                    <td style="width: 150px">Name</td>
                                                    <td colspan=2><?php echo $row['info'] ?></td>
                                                </tr>
                                                <tr>
                                                    <td>Type</td>
                                                    <td colspan=2><?php echo gettype($var); ?></td>
                                                </tr>
                                                <tr>
                                                    <td>Value</td>
                                                    <td colspan=2><pre style="max-width: 450px;max-height: 300px;overflow: auto;"><?php echo displayVar($var); ?></td>
                                                </tr>
                                                <?php if($type=='double' || $type=='string' || $type=='integer'){ ?>
                                                <tr>
                                                    <td>Edit value</td>
                                                    <td colspan=2><textarea class="form-control" name="value"><?php echo $var ?></textarea></td>
                                                </tr>
                                                <?php  } ?>
                                                <tr>
                                                    <td>Expire on</td>
                                                    <td><input name="ttl" type="text" style="width: 200px" class="form-control" value="<?php echo ($row['ttl']==0)?'never':$row['ttl'] ?>"/></td>
                                                    <td>Seconds <small>(0 if never expires)</small></td>
                                                </tr>
                                            </table>
                                        </div>
                                    </td>
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
                                        <button type="button" class="btn btn-danger remove_cache_user" ajaxr="remove_cache_user=<?php echo $row['info'] ?>"><span class="glyphicon glyphicon-trash"></span></button>
                                    </td>
                                </tr>
                                <?php
                            }
                        }
                        ?>
                    </tbody>
                </table>
                <?php } ?>
            </div>
        </div>
        
    </div>
</div>


<div class="modal fade" id="myModal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
        <h4 class="modal-title"></h4>
      </div>
      <div class="modal-body">
        
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
        <button type="button" class="btn btn-primary" id="save_variable">Save changes</button>
        <button type="button" class="btn btn-danger remove_cache_user_popup" style="float: left">
            <span class="glyphicon glyphicon-trash"></span>
        </button>
      </div>
    </div>
  </div>
</div>
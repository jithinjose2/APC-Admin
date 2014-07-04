<div class="col-md-6" style="padding-left: 0px">
    
    <div class="panel panel-info">
        <div class="panel-heading">
            <h3 class="panel-title">General Cache Information</h3>
        </div>
        <div class="panel-body" style="padding: 0px">
            <table class="table table-striped table-bordered">
                <tbody>
                    <tr><td>APC Version</td><td><?php echo $apcversion ?></td></tr>
                    <tr><td>PHP Version</td><td><?php echo $phpversion ?></td></tr>
                    <?php if(!empty($_SERVER['SERVER_NAME'])) { ?>
                        <tr><td>APC Host</td><td><?php echo $_SERVER['SERVER_NAME']." ".$host?></td></tr>
                        
                    <?php } if(!empty($_SERVER['SERVER_SOFTWARE'])) { ?>
                        <tr><td>Server Software</td><td><?php echo $_SERVER['SERVER_SOFTWARE']?></td></tr>
                    <?php } ?>
                    
                    <tr><td>Shared Memory</td><td><?php echo $mem['num_seg']?> Segment(s) with <?php echo $seg_size?> 
                        <br/> (<?php echo $cache['memory_type']?> memory, <?php echo $cache['locking_type']?> locking)
                        </td>
                    </tr>
                    <tr><td>Start Time</td><td><?php echo date(DATE_FORMAT,$cache['start_time'])?></td></tr>
                    <tr><td>Uptime</td><td><?php echo duration($cache['start_time'])?></td></tr>
                    <tr><td>File Upload Support</td><td><?php echo $cache['file_upload_progress']?></td></tr>
                </tbody>
            </table>
        </div>
    </div>
    
</div>

<div class="col-md-6" style="padding-left: 0px">
    <?php if(count(ini_get_all('apc'))==0 && count(ini_get_all('apcu'))>0){ ?>
            <div class="alert alert-warning" role="alert">
                    You are running APCu instead of APC, some options will not work properly
            </div>
    <?php } ?>
                                        
    <div class="panel panel-info">
        <div class="panel-heading">
            <h3 class="panel-title">Memory allocation and usage</h3>
        </div>
        <div class="panel-body">
        
            <div id="chat_container_status"></div>
            <script>
                $(function () {
                    $('#chat_container_status').highcharts({
                        chart: {
                            plotBackgroundColor: null,
                            plotBorderWidth: null,
                            plotShadow: false
                        },
                        tooltip: {
                            pointFormat: '{series.name}: <b>{point.percentage:.1f}%</b>'
                        },
                        plotOptions: {
                            pie: {
                                allowPointSelect: true,
                                cursor: 'pointer',
                                dataLabels: {
                                    enabled: false
                                },
                                showInLegend: true
                            }
                        },
                        credits: {
                            enabled: false
                        },
                        series: [{
                            type: 'pie',
                            name: 'Host Status Diagrams - Memory Usage',
                            data: [
                                ['Cached Files - ' + '<?php echo $size_files?>',   <?php echo $cache['mem_size'];?> ],
                                ['Cached Variables - ' + '<?php echo $size_vars?>',  <?php echo $cache_user['mem_size'];?>],
                                ['Free Space - ' + '<?php echo bsize($mem_avail)?>',    <?php echo $mem_avail?>]
                            ]
                        }]
                    });
                });
            </script>
        </div>
    </div>
</div>
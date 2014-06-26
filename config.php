


<div class="col-md-12">
    
    <div class="panel panel-info">
        <div class="panel-heading">
            <h3 class="panel-title">Runtime Settings</h3>
        </div>
        <div class="panel-body" style="padding: 0px">
            
            <table class="table table-striped table-bordered">
                <tbody>
                    <?php
                    $i = 0;
                    foreach (ini_get_all('apc') as $k => $v) {
                        if($i%2==0){
                            echo '<tr>';
                        }
                        $i++;
                        echo "<td>",$k,"</td><td>",str_replace(',',',<br />',$v['local_value']),"</td>";
                        if($i%2==0){
                            echo '</tr>';
                        }
                    }
                    ?>
                </tbody>
            </table>
            
        </div>
    </div>
    
</div>
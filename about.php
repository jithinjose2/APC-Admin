<div class="col-md-12">
    <script>
        $(document).ready(function(){
            $("#about_link").click(function(){
                $.ajax({url:'apc.php?about_info=full'}).done(function(data){
                    $("#apc_about").html(data);
                })
            })
        })
    </script>
    <div class="panel panel-info">
        <div class="panel-heading">
            <h3 class="panel-title">About APC Admin</h3>
        </div>
        <div class="panel-body">
            <div class="col-md-10">
                Author : JITHIN JOSE</br>
                Version : 1.2</br>
                APC Admin is created and maintained by <a href="http://www.techzonemind.com/"><img src="http://www.techzonemind.com/labz/logo.jpg" style="height: 12px;margin-bottom: 1px;margin-right: 2px"/>TechZoneMid</a>. Free to use,modify and and redistribute. </br>
                For anyquerys/bug reports please visit github rep
            </div>
            <div class="col-md-2">
                <a href="http://www.techzonemind.com/">
                    <img src="http://www.techzonemind.com/labz/logo.jpg"/>
                </a>
            </div>
        </div>
    </div>
    
    <div class="panel panel-info">
        <div class="panel-heading">
            <h3 class="panel-title">APC Version Information</h3>
        </div>
        <div class="panel-body" id="apc_about">
            <div class="alert alert-info">Please wait loading updated  information</div>
        </div>
    </div>

</div>
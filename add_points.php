<?php
   include('session.php');
   $username = $login_session;
   $route_id = $_GET['route_id'];
   
   $sql = "SELECT * FROM route WHERE owner='$username' and route_id='$route_id'";
   $result = mysqli_query($db,$sql);
   $row = mysqli_fetch_array($result, MYSQLI_ASSOC);
   
   //Make sure user viewing route owns this route
   $count = mysqli_num_rows($result);
  if($count != 1) {
      header("location: welcome.php");
  }
   
   $route_name = $row['name'];
   
   //get points for this route
   $sql = "SELECT * FROM point WHERE parent_route=$route_id";
   $result_set = mysqli_query($db,$sql);
   $point_arr = array();
   $i = 0;
   while($row = mysqli_fetch_row($result_set)) {
      $point_arr[$i++] = $row;
   }
   
   
   if($_SERVER["REQUEST_METHOD"] == "POST") {
        $lat = mysqli_real_escape_string($db, $_POST['lat']);
        $long = mysqli_real_escape_string($db, $_POST['long']);
        
        $sql = "SELECT MAX(point_order) FROM point WHERE parent_route='$route_id'";
        $result = mysqli_query($db, $sql);
        $order = mysqli_fetch_row($result)[0] + 1;
        
        $sql = "INSERT INTO point(point_order, parent_route, latitude, longitude) VALUES ('$order', '$route_id', '$lat', '$long')";
        $result = mysqli_query($db, $sql);
        if($result) {
            // header("location: welcome.php");
        } else {
            $error = "Error: ".$sql_insert." ".mysqli_error($db);
        }
        $sql = "SELECT * FROM point WHERE parent_route=$route_id";
        $result_set = mysqli_query($db,$sql);
        $point_arr = array();
        $i = 0;
        while($row = mysqli_fetch_row($result_set)) {
           $point_arr[$i++] = $row;
        }
    }
?>
<html">
   
   <head>
      <title>Route Page</title>
      <style type = "text/css">
      body {
      font-family:Arial, Helvetica, sans-serif;
      font-size:14px;
      margin: 0px;
      }
      label {
      font-weight:bold;
      width:100px;
      font-size:14px;
      }
      .box {
      border:#666666 solid 1px;
      }
   </style>
      <script src="https://cdn.rawgit.com/openlayers/openlayers.github.io/master/en/v5.2.0/build/ol.js"></script>

   
   <link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.4.2/css/all.css" integrity="sha384-/rXc/GQVaYpyDdyxK+ecHPVYJSN9bmVFBvjA/9eOB+pb3F2w2N6fc5qB9Ew5yIns" crossorigin="anonymous">
   <script src="https://cdn.rawgit.com/openlayers/openlayers.github.io/master/en/v5.2.0/build/ol.js"></script>
   </head>
   
   <body bgcolor="#f2f7f9">
    <div align="center">
    <div style="background-color:#b1bfca; color:#ffffff; display:flex; align-items:center; flex: 1">
      <h1 style="padding-left: 10px">Viewing Route: <?php echo $route_name; ?></h1> 
      <h3 style="flex: 1; display:flex; justify-content:flex-end">
         <a style="aign-self: flex-end; padding-right: 10px" href = "welcome.php">Back</a></h2>
   </div>
   <p id="debug"></p>
    <div style = "font-size:11px; color:#cc0000; margin-top:10px"><?php echo $error; ?></div>
    
        <?php 
            foreach($point_arr as $point) {
                echo "<div><text>Order: ".$point[0]." Lat: ".$point[2]." Long: ".$point[3]."</text></div>";
            }
        ?>
        <form action="" method="post")>
            <div style="padding-left: 10px"><text>Lat: </text><input type="text" name="lat" id="lat"/></div>
            <div><text>Long: </text><input type="text" name="long" id="lon"/></div>
            <div><input type="submit" value="Add Point"/></div>
        </form>
    <?php 
        echo "Order: ".$order;
    ?>
    
    
    <div align="center">
        <div id="map" class="map"></div>
        </div>
    <script type="text/javascript">
    
 
        var osmSource = new ol.source.OSM()
        
        var projection = ol.proj.get('EPSG:900913');
        var tileGrid = ol.tilegrid.createXYZ({
          extent: projection.getExtent(),
          tileSize: 64,
          maxZoom: 15,
          minZoom: 15
        });
        
        var source = new ol.source.Vector({wrapX: false});

        var vector = new ol.layer.Vector({
            source: source
        });
        
        var center = [-87.62, 41.88];
        var centerProj = ol.proj.fromLonLat(center);
        
        
        function mapInteractions_downEvent(evt) { //This event fire when a click was caught on the map
        
            var coord = ol.proj.toLonLat(evt.coordinate);
        
            //document.getElementById("debug").innerHTML = coord;
            
            document.getElementById("lon").value = coord[0];
            document.getElementById("lat").value = coord[1];
            
        	
            return true; 
        }
        
        
        mapMouseEvents = function () {
            ol.interaction.Pointer.call(this, {
              handleDownEvent: mapInteractions_downEvent
            });
        
            this.lastCursorPosition = undefined;
          };
          ol.inherits(mapMouseEvents, ol.interaction.Pointer);
          mapInteractions = new ol.interaction.defaults({}).extend([new mapMouseEvents]);
        
        var map = new ol.Map({
            interactions: mapInteractions,
            target: 'map',
            layers: [
              new ol.layer.Tile({
                source: osmSource
              }),
              
              vector
              
            ],
            view: new ol.View({
              center: centerProj,
              zoom: 12
            })
        });
      
        
      
       
        
        
        
        map.on('postcompose', function(event) {
            var vectorContext = event.vectorContext;
            var frameState = event.frameState;
            var i;
            
           
            
            
            
            
            
            var coordinates0 = JSON.parse( '<?php echo json_encode($point_arr) ?>' );
            
            
            
            
            for(i = 0; i < coordinates0.length; i++)
            {
                var newPoint = ol.proj.fromLonLat([
                parseFloat(coordinates0[i][3]), 
                parseFloat(coordinates0[i][2])
                ]);
                
                
                
                var curRed = 0; //scores0[i] / maxScore * 255;
                
                //document.getElementById("debug").innerHTML = maxScore;
                
                var imageStyle = new ol.style.Style({
                    image: new ol.style.Circle({
                      radius: 7,
                      fill: new ol.style.Fill({color: new Array(curRed, 255 - curRed, 255 - curRed * 2, 1.0)}),
                      stroke: new ol.style.Stroke({color: 'red', width: 1})
                    })
                });
                
                vectorContext.setStyle(imageStyle);
                vectorContext.drawGeometry(new ol.geom.Point(newPoint));
            }
            
            
            //document.getElementById("debug").innerHTML = coordinates;
            
            
            map.render();
            
            
        
        });
        
        
            

        
        map.render();
        
      
    </script>
    
   </body>
   
</html>
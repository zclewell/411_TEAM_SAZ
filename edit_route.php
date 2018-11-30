<?php

    function insert($array, $index, $val)
    {
       $size = count($array);
       if (!is_int($index) || $index < 0 || $index > $size)
       {
           $array[$index] = $val;
           return $array;
       }
       else
       {
           $temp   = array_slice($array, 0, $index);
           $temp[] = $val;
           $inc_array = array();
           $post_arr = array_slice($array, $index, $size);
           foreach($post_arr as $k => $v) {
               $v[0] += 1;
               $inc_array[$k] = $v;
           }
           return array_merge($temp, $inc_array);
       }
    }
    
    function delete($array, $idx) {
        array_splice($array, $idx, 1);
    }

    // repeated code, we should make stuff functions later..
    include('session.php');
    $username = $login_session;
    $route_id = $_SESSION["prev_id"];

    $sql = "SELECT * FROM route WHERE owner='$username' and route_id='$route_id'";
    $result = mysqli_query($db,$sql);
    $row = mysqli_fetch_array($result, MYSQLI_ASSOC);
   
    //Make sure user viewing route owns this route
    $count = mysqli_num_rows($result);
    if($count != 1) {
       header("location: welcome.php");
    }
   
    $sql = "SELECT MAX(point_order) FROM point WHERE parent_route='$route_id'";
    $result = mysqli_query($db, $sql);
    $max_idx = mysqli_fetch_row($result)[0] + 1;
   
    $route_name = $row['name'];
    if(isset($_SESSION[$route_id])) {
        $point_arr = $_SESSION[$route_id];
    } else {
        //echo "cache not found";
        $sql = "SELECT * FROM point WHERE parent_route=$route_id";
        //$sql = "SELECT * FROM crime_point";
        $result_set = mysqli_query($db,$sql);
        $point_arr = array();
        $i = 0;
        while($row = mysqli_fetch_row($result_set)) {
           $point_arr[$i++] = $row;
        }
    }
    //get points for this route
    
    /* works, but insert should be a more general case of this.
    if($_POST["append_pt"]) {
        $lat = mysqli_real_escape_string($db, $_POST['lat']);
        $long = mysqli_real_escape_string($db, $_POST['long']);
        
        $sql = "SELECT MAX(point_order) FROM point WHERE parent_route='$route_id'";
        $result = mysqli_query($db, $sql);
        $order = mysqli_fetch_row($result)[0] + 1;
        
        $sql = "INSERT INTO point (point_order, parent_route, latitude, longitude) VALUES ('$order', '$route_id', '$lat', '$long')";
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
    */
    if($_POST["insert_point"]) {
        // $order_arr = $_POST['id'];
        // implode(',',$_POST['id']);
        $order_id = mysqli_real_escape_string($db, $_POST['idx']);
        if ((int)$max_idx >= (int)$order_id and (int)$order_id > 0) {
            $point_arr = insert($point_arr, (int)$order_id, array($order_id, $route_id, $_POST['lat'], $_POST['long'], null));
            $lat = floatval($_POST['lat']);
            $long = floatval($_POST['long']);
            $sql = "UPDATE point SET point_order = point_order + 1 WHERE parent_route='$route_id' AND point_order >= " . $order_id . " ORDER BY point_order DESC";
            $result = mysqli_query($db, $sql);
            if(!$result) {
                //echo "Updating fail on inserting pt";
            }
            $sql = "INSERT INTO point (point_order, parent_route, latitude, longitude) VALUES ('$order_id', '$route_id', '$lat', '$long')";
            $result = mysqli_query($db, $sql);
            if(!$result) {
                echo "Insert fail on inserting pt ", mysqli_error($db);
            }
            
        }
        $sql = "SELECT * FROM point WHERE parent_route=$route_id";
        //$sql = "SELECT * FROM crime_point";
        $result_set = mysqli_query($db,$sql);
        $point_arr = array();
        $i = 0;
        while($row = mysqli_fetch_row($result_set)) {
           $point_arr[$i++] = $row;
        }
        $sql = "SELECT MAX(point_order) FROM point WHERE parent_route='$route_id'";
        $result = mysqli_query($db, $sql);
        $max_idx = mysqli_fetch_row($result)[0] + 1;
        
    }
    
    if($_POST["delete_point"]) {
        $delete_idx = (int)$_POST["delete_point"];

        $sql = "DELETE FROM point WHERE parent_route='$route_id' AND point_order='$delete_idx'";
        $result = mysqli_query($db, $sql);
        if(!$result) {
            //echo "Delete fail on delete pt";
        }
        $sql = "UPDATE point SET point_order = point_order - 1 WHERE parent_route='$route_id' AND point_order >= " . $delete_idx . " ORDER BY point_order ASC";
        $result = mysqli_query($db, $sql);
        if(!$result) {
            //echo "Updating fail on deleting pt";
        }
        $sql = "SELECT * FROM point WHERE parent_route=$route_id";
        //$sql = "SELECT * FROM crime_point";
        $result_set = mysqli_query($db,$sql);
        $point_arr = array();
        $i = 0;
        while($row = mysqli_fetch_row($result_set)) {
           $point_arr[$i++] = $row;
        }
        $sql = "SELECT MAX(point_order) FROM point WHERE parent_route='$route_id'";
        $result = mysqli_query($db, $sql);
        $max_idx = mysqli_fetch_row($result)[0] + 1;
        echo $max_idx;
    }
    asort($point_arr);
    $_SESSION[$route_id] = $point_arr;

?>
    
    <head>
      <link rel="stylesheet" href="https://cdn.rawgit.com/openlayers/openlayers.github.io/master/en/v5.2.0/css/ol.css" type="text/css">
    <style>
      .map {
        height: 50%;
        width: 100%;
      }
      body {
      font-family: Arial, Helvetica,sans-serif;
      font-size:14px;
      margin:0px;
      padding:0px;
      }
    </style>
    <script src="https://cdn.rawgit.com/openlayers/openlayers.github.io/master/en/v5.2.0/build/ol.js"></script>
    <title>Route Map</title>
   </head>
   
   <body bgcolor="#f2f7f9">
       <div align="center">
   <div style="background-color:#b1bfca; color:#ffffff; display:flex; align-items:center; flex: 1">
      <h1 style="padding-left: 10px">Editing <?php echo $route_name; ?></h1>
      <h3 style="flex: 1; display:flex; justify-content:flex-end">
         <a style="align-self: flex-end; padding-right: 10px" href = "route_map.php?route_id=<?php echo $_SESSION["prev_id"]; ?>">Back</a></h2>
   </div>
   <div style = "font-size:11px; color:#cc0000; margin-top:10px"><?php echo $error; ?></div>
        <form action="" method="post">`
            <div align=\"center\">
        <?php 
            foreach($point_arr as $point) {
                echo "<div><text>Order: ".$point[0]." Lat: ".$point[2]." Long: ".$point[3]."</text></div>";
                echo "<div><input type=\"submit\" name=\"delete_point\" value=\"".$point[0]."\">Delete</div>";
            }
        ?>
        </div>
        </form>
        <form action="" method="post">
            <div style="padding-left: 10px"><text>Lat: </text><input type="text" name="lat" id="lat"/></div>
            <div><text>Long: </text><input type="text" name="long" id="lon"/></div>
            <div style="padding-left: 10px"><text>Index: </text><input type="text" value=<?php echo $max_idx?> name="idx"/></div>
            <div><input type="submit" name="insert_point" value="insert point"/></div>
        </form>
    </div>
    
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
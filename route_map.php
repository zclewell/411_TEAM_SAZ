<?php
   function checkbox_format() {
       $query_format = "WHERE crime";
       $crimes = array('KIDNAPPING', 'CONCEALED CARRY LICENSE VIOLATION', 'PUBLIC PEACE VIOLATION', 'INTERFERENCE WITH PUBLIC OFFICER', 'PROSTITUTION', 'LIQUOR LAW VIOLATION', 'RITUALISM', 'ROBBERY', 'BURGLARY', 'WEAPONS VIOLATION', 'HUMAN TRAFFICKING', 'OTHER NARCOTIC VIOLATION', 'HOMICIDE', 'OBSCENITY', 'OTHER OFFENSE', 'crime', 'CRIMINAL DAMAGE', 'THEFT', 'OFFENSE INVOLVING CHILDREN', 'GAMBLING', 'PUBLIC INDECENCY', 'NON-CRIMINAL (SUBJECT SPECIFIED)', 'ARSON', 'NARCOTICS', 'SEX OFFENSE', 'STALKING', 'INTIMIDATION', 'DECEPTIVE PRACTICE', 'BATTERY', 'NON - CRIMINAL', 'CRIMINAL TRESPASS', 'MOTOR VEHICLE THEFT', 'ASSAULT', 'CRIM SEXUAL ASSAULT', 'NON-CRIMINAL');
       foreach ($_POST as $key => $value) {
           if (in_array(strtoupper($key), $crimes)) {
               $query_format .= "='" . strtoupper($key) . "' OR crime";
           }
       }
       if (strlen($query_format) == 11) {
           // its just the where
           $query_format = "";
       }
       if (strlen($query_format) > 0) {
           $query_format = substr($query_format, 0, -9);
       }
       return $query_format;
   }

   include('session.php');
   
   $username = $login_session;
   $route_id = $_GET['route_id'];
   $_SESSION["prev_id"] = $route_id;
   echo $_SESSION["prev_id"];
   
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
   //$sql = "SELECT * FROM crime_point";
   $result_set = mysqli_query($db,$sql);
   $point_arr = array();
   $i = 0;
   while($row = mysqli_fetch_row($result_set)) {
      $point_arr[$i++] = $row;
   }

   
   
   //look for similar routes
  $start_point = $point_arr[0];
  $end_point = $point_arr[$i-1];
  $max_distance = 0.01;
  $sql = 
  "SELECT x4.name, x4.parent_route
  FROM
        (SELECT 
            r1.name, x3.parent_route, x3.start_lat, x3.start_long, x3.end_lat, x3.end_long 
        FROM 
                (SELECT 
                    x1.parent_route, x1.latitude AS start_lat, x1.longitude AS start_long, x2.latitude AS end_lat, x2.longitude AS end_long 
                FROM 
                    (SELECT 
                        p1.parent_route, p1.latitude, p1.longitude 
                    FROM 
                        point p1 
                    WHERE 
                        p1.point_order = (SELECT MIN(point_order) FROM point p2 WHERE p1.parent_route = p2.parent_route)) x1,
                    (SELECT 
                        p3.parent_route, p3.latitude, p3.longitude 
                    FROM 
                        point p3 
                    WHERE 
                        p3.point_order = (SELECT MAX(point_order) FROM point p4 WHERE p3.parent_route = p4.parent_route)) x2 
                        WHERE x1.parent_route = x2.parent_route  AND x1.parent_route != $route_id) x3 
                JOIN 
                    route r1 
                ON 
                    r1.route_id = x3.parent_route) x4
        WHERE SQRT(POW($start_point[2]-x4.start_lat,2)+POW($start_point[3]-x4.start_long,2)) < $max_distance AND SQRT(POW($end_point[2]-x4.end_lat,2)+POW($end_point[3]-x4.end_long,2)) < $max_distance ";
  $result_set = mysqli_query($db,$sql);
  if(!$result_set) {
      $error = "Error: ".mysqli_error($db);
  }
  
  $similar_route_ids = array();
  $j = 0;
  while($row = mysqli_fetch_row($result_set)) {
       $similar_route_ids[$j++] = $row;
   }
   
   if ($_POST["1-closest"] || $_POST["10-closest"] || $_POST["range"]) { // use the k-closest
       $k = 1;
       echo "K set to ", strval($k);
       if ($_POST["10-closest"]) {
           $k = 10;
           echo "K set to ", strval($k);
       }
       // you can do range stuff here I suppose zach
       $closest_crime_pts = array(); // 2d array of size #pts, k where each entry is (lat, long, dist) for each of the k closest points.
       $point_inv_dists = array(); // 1d array of size # pts, each value is sum of inverse dists to k closest pts.
       for($x = 0; $x < count($point_arr); $x++) {
           $lat = $point_arr[$x][2];
           $long = $point_arr[$x][3];
           $types = checkbox_format();
           $query = "SELECT cp.latitude, cp.longitude, SQRT(POW(ABS(($lat - cp.latitude)), 2) + POW(ABS(($long - cp.longitude)), 2)) as dist FROM crime_point cp $types ORDER BY dist LIMIT $k"; // note I can get many pts here
           //echo $query;
           $result = mysqli_query($db, $query);
           if($result) {
               $i = 0;
               $inv_dist = 0.0;
               while($row = mysqli_fetch_row($result)) {
                  $inv_dist_cur = 1 / floatval($row[2]);
                  $inv_dist += $inv_dist_cur;
                  $closest_crime_points[$x][$i++] = 
                  [$row[0], $row[1], $inv_dist_cur];
               }
               echo "Current inv dist sum: ", strval($inv_dist), " ";
               $point_inv_dists[$x] = $inv_dist;
           } else {
               $error = "Error: ".$sql_insert." ".mysqli_error($db);
               echo $error;
           }
       }
   }
   
   if($_SERVER["REQUEST_METHOD"] == "POST" and !($_POST["1-closest"] or $_POST["range"] or $_POST["10-closest"])) {
       $sql_delete = "DELETE FROM point WHERE parent_route=$route_id";
       $result = mysqli_query($db, $sql_delete);
       if($result) {
            $sql_delete = "DELETE FROM route WHERE route_id=$route_id";
            $result = mysqli_query($db, $sql_delete);
            if($result) {
                header("location: welcome.php");
            } else {
                $error = "Error: ".$sql_insert." ".mysqli_error($db);
            }
       } else{
           $error = "Error: ".$sql_insert." ".mysqli_error($db);
       }
   }
?>
<html">
   
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
   <div style="background-color:#b1bfca; color:#ffffff; display:flex; align-items:center; flex: 1">
      <h1 style="padding-left: 10px"><?php echo $route_name; ?></h1>
      <h3 style="flex: 1; display:flex; justify-content:flex-end">
         <a style="align-self: flex-end; padding-right: 10px" href = "edit_route.php">Edit</a>
         <a style="align-self: flex-end; padding-right: 10px" href = "welcome.php">Back</a></h2>
   </div>

    
    <!--<p id="debug"></p>-->

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
        
        var map = new ol.Map({
            target: 'map',
            layers: [
              new ol.layer.Tile({
                source: osmSource
              }),
              
              vector
              
              /*new ol.layer.Tile({
                source: new ol.source.TileDebug({
                projection: projection,
                tileGrid: tileGrid
                })
              })*/
            ],
            view: new ol.View({
              center: centerProj,
              zoom: 12
            })
        });
      
        /*
        var draw; 
        
        function addInteraction() {
            geometryFunction = ol.interaction.Draw.createBox();
          
            draw = new ol.interaction.Draw({
                source: source,
                type: 'Circle',
                geometryFunction: geometryFunction
            });
            map.addInteraction(draw);
        }
      
        addInteraction();
        */
        
        
        var imageStyle = new ol.style.Style({
            image: new ol.style.Circle({
              radius: 5,
              fill: new ol.style.Fill({color: 'yellow'}),
              stroke: new ol.style.Stroke({color: 'red', width: 1})
            })
        });
      
        var n = 50;
        var omegaTheta = 30000; // Rotation period in ms
        var R = 7e6;
        var r = 2e6;
        var p = 2e6;
        
        var boxCoord = [[0, 0], [0.002, 0.002]];
        
        boxCoord[0][0] += centerProj[0];
        boxCoord[0][1] += centerProj[1];
        boxCoord[1][0] += centerProj[0];
        boxCoord[1][1] += centerProj[1];
        
        map.on('postcompose', function(event) {
            var vectorContext = event.vectorContext;
            var frameState = event.frameState;
            var theta = 2 * Math.PI * frameState.time / omegaTheta;
            var coordinates = [];
            var i;
            
           
            
            
            
            
            
            var coordinates0 = JSON.parse( '<?php echo json_encode($point_arr) ?>' );
            
            //document.getElementById("debug").innerHTML = coordinates0.length;
            
            
            for(i = 0; i < coordinates0.length; i++)
            {
                var newPoint = ol.proj.fromLonLat([
                parseFloat(coordinates0[i][3]), 
                parseFloat(coordinates0[i][2])
                ]);
                
                //document.getElementById("debug").innerHTML = newPoint;
                
                coordinates.push(newPoint);
            }
            
            //document.getElementById("debug").innerHTML = coordinates;
            
            
            
            vectorContext.setStyle(imageStyle);
            /*
            var feature = new ol.Feature({
              geometry: new ol.geom.Polygon([coordinates])
              //: new ol.geom.Point(centerProj),
              //name: 'My Polygon'
            });*/
            
            
            
            //vectorContext.drawGeometry(geometry);
            
            vectorContext.drawGeometry(new ol.geom.MultiPoint(coordinates));
            
            //vectorContext.drawPoint(new ol.geom.Point(centerProj));
            //vectorContext.drawGeometry(new ol.geom.Polygon(coordinates));
            
            /*
            var headPoint = new Point(coordinates[coordinates.length - 1]);
    
            vectorContext.setStyle(headOuterImageStyle);
            vectorContext.drawGeometry(headPoint);
    
            vectorContext.setStyle(headInnerImageStyle);
            vectorContext.drawGeometry(headPoint);
            */
            map.render();
        });
        map.render();
        
      
    </script>
    <?php
        echo "<div><text>$error</text></div>";
        ?>
    <div><h3>Similar Routes:</h3>
            <?php 
            foreach($similar_route_ids as $row) {
                echo "<div style=\"padding-left:10px; padding-top:5px; padding-bottom:5px; align-self: start;\"><a href=\"read_only_route_map.php?route_id=".$row[1]."\"> ".$row[0]." </a></div>";
            }
        ?>
        </div>
    <form action="" method="post")>
        <div align="center"><input type="submit" value="Delete Route"/></div>
    </form>
    <form action="<?php $_PHP_SELF ?>" method="post")>
        <div align="left"><input type="submit" name="1-closest" value="1-Closest"/></div>
        <div align="left"><input type="submit" name="10-closest" value="10-Closest"/></div>
        <div align="left"><input type="submit" name="range" value="Range"/></div>
        <div align="left"><input type="checkbox" name="Robbery" value="ROBBERY">Robbery<br></div>
        <div align="left"><input type="checkbox" name="Burglary" value="BURGLARY">Burglary<br></div>
        <div align="left"><input type="checkbox" name="Misc" value="OTHER OFFENSE">Other<br></div>
        <div align="left"><input type="checkbox" name="Homicide" value="HOMICIDE">Homicide<br></div>
        <div align="left"><input type="checkbox" name="Damages" value="CRIMINAL DAMAGE">Damages<br></div>
        <div align="left"><input type="checkbox" name="Theft" value="THEFT">Theft<br></div>
        <div align="left"><input type="checkbox" name="Narcotics" value="NARCOTICS">Narcotics<br></div>
        <div align="left"><input type="checkbox" name="Deceptive Practice" value="DECEPTIVE PRACTICE">Deceptive Practice<br></div>
        <div align="left"><input type="checkbox" name="Battery" value="BATTERY">Battery<br></div>
        <div align="left"><input type="checkbox" name="Trespass" value="CRIMINAL TRESPASS">Trespassing<br></div>
        <div align="left"><input type="checkbox" name="Vehicle theft" value="MOTOR VEHICLE THEFT">Vehicle Theft<br></div>
        <div align="left"><input type="checkbox" name="Assault" value="ASSAULT">Assault<br></div>
    </form>
   </body>
   
</html>

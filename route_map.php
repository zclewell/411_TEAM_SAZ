<?php
   
   function checkbox_format($crime_array = "") {
       $query_format = " AND crime";
       $crimes = array('KIDNAPPING', 'CONCEALED CARRY LICENSE VIOLATION', 'PUBLIC PEACE VIOLATION', 'INTERFERENCE WITH PUBLIC OFFICER', 'PROSTITUTION', 'LIQUOR LAW VIOLATION', 'RITUALISM', 'ROBBERY', 'BURGLARY', 'WEAPONS VIOLATION', 'HUMAN TRAFFICKING', 'OTHER NARCOTIC VIOLATION', 'HOMICIDE', 'OBSCENITY', 'OTHER OFFENSE', 'crime', 'CRIMINAL DAMAGE', 'THEFT', 'OFFENSE INVOLVING CHILDREN', 'GAMBLING', 'PUBLIC INDECENCY', 'NON-CRIMINAL (SUBJECT SPECIFIED)', 'ARSON', 'NARCOTICS', 'SEX OFFENSE', 'STALKING', 'INTIMIDATION', 'DECEPTIVE PRACTICE', 'BATTERY', 'NON - CRIMINAL', 'CRIMINAL TRESPASS', 'MOTOR VEHICLE THEFT', 'ASSAULT', 'CRIM SEXUAL ASSAULT', 'NON-CRIMINAL');
       foreach ($_POST as $key => $value) {
           if (in_array(strtoupper($key), $crimes)) {
               $query_format .= "='" . strtoupper($key) . "' OR crime";
               $crime_array .= $key . ", ";
           }
       }
       if (strlen($query_format) == 10) {
           // its just the crime
           $query_format = "";
       }
       if (strlen($query_format) > 0) {
           $query_format = substr($query_format, 0, -9);
       }
       if (strlen($crime_array) > 0) {
           $crime_array = substr($crime_array, 0, -2);
       }
       return array($query_format, $crime_array);
   }

   include('session.php');
   $crime_types_list = checkbox_format()[1];
   $username = $login_session;
   $route_id = $_GET['route_id'];
   $_SESSION["prev_id"] = $route_id;
//   echo $_SESSION["prev_id"];
   
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
   $point_score_arr = array();
   $i = 0;
   while($row = mysqli_fetch_row($result_set)) {
      $point_score_arr[$i] = 0;
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
  $type_of_scoring = "";
  $similar_route_ids = array();
  $j = 0;
  while($row = mysqli_fetch_row($result_set)) {
       $similar_route_ids[$j++] = $row;
   }
    $crime_point_arr = array();
   if ($_POST["10-closest"]) { // use the k-closest
       $k = 1;
       $c = 1900;
    //   echo "K set to ", strval($k);
       if ($_POST["10-closest"]) {
           $k = 10;
           $c = 1750;
           $type_of_scoring = "10-closest";
        //   echo "K set to ", strval($k);
       } else {
           $type_of_scoring = "1-closest";
       }
       
       $crime_point_arr = array();
       $crime_point_id = 0;
       // you can do range stuff here I suppose zach
       
   
        
       
      
       $closest_crime_pts = array(); // 2d array of size #pts, k where each entry is (lat, long, dist) for each of the k closest points.
       $point_inv_dists = array(); // 1d array of size # pts, each value is sum of inverse dists to k closest pts.
       for($x = 0; $x < count($point_arr); $x++) {
           $lat = $point_arr[$x][2];
           $long = $point_arr[$x][3];
           $types = checkbox_format($crime_types_list)[0];
           $query = "SELECT cp.latitude, cp.longitude, SQRT(POW(ABS(($lat - cp.latitude)), 2) + POW(ABS(($long - cp.longitude)), 2)) as dist FROM crime_point cp WHERE 1=1 $types ORDER BY dist LIMIT $k"; // note I can get many pts here
           //echo $query;
           $result = mysqli_query($db, $query);
           if($result) {
               $i = 0;
               $inv_dist = 0.0;
               while($row = mysqli_fetch_row($result)) {
                  $crime_point_arr[$crime_point_id++] = array($row[0],$row[1]);
                  $inv_dist_cur = 1 / floatval($row[2]);
                  $inv_dist += $inv_dist_cur;
                  $closest_crime_points[$x][$i++] = 
                  [$row[0], $row[1], $inv_dist_cur];
               }
            //   echo "Current inv dist sum: ", strval($inv_dist), " ";
               $point_inv_dists[$x] = $inv_dist;
               $point_score_arr[$x] = $inv_dist;
           } else {
               $error = "Error: ".$sql_insert." ".mysqli_error($db);
               echo $error;
           }
           
       }
       for($i=0; $i<count($point_score_arr); $i++) {
           $point_score_arr[$i] = $c  * ($point_score_arr[$i] - min($point_score_arr)) / (max($point_score_arr) - min($point_score_arr));
       }
    } else if($_POST["range"]) {
        $type_of_scoring = "Range";
        $crime_point_arr = array();
       $crime_point_id = 0;
           $default_range = 0.0025;
           $c = 900;
           for($x = 0; $x < count($point_arr); $x++) {
                $lat = $point_arr[$x][2];
                $long = $point_arr[$x][3];
                $types = checkbox_format($crime_types_list)[0];
                $query = "Select cp.latitude, cp.longitude FROM crime_point cp WHERE (SQRT(POW(ABS(($lat - cp.latitude)), 2) + POW(ABS(($long - cp.longitude)), 2))) < $default_range $types";
                $result = mysqli_query($db, $query);
                if($result) {
                    $sum = 0;
                    while($row = mysqli_fetch_row($result)) {
                        $sum += 1;
                        $crime_point_arr[$crime_point_id++] = array($row[0],$row[1]);
                    }
                    $point_score_arr[$x] = $sum;
                } else {
                    $point_score_arr[$x]= 0;
                    $error = "Error: ".$query." ".mysqli_error($db);
               echo $error;
                }
           }
           for($i=0; $i<count($point_score_arr); $i++) {
               if ($point_score_arr[$i] != 0.0) {
                $point_score_arr[$i] = $c  * log10($point_score_arr[$i]);
               }
           }
        } else {
            $k = 1;
       $c = 1900;
    //   echo "K set to ", strval($k);
       if ($_POST["10-closest"]) {
           $k = 10;
           $c = 1750;
           $type_of_scoring = "10-closest";
        //   echo "K set to ", strval($k);
       } else {
           $type_of_scoring = "1-closest";
       }
       
       $crime_point_arr = array();
       $crime_point_id = 0;
       // you can do range stuff here I suppose zach
       
   
        
       
      
       $closest_crime_pts = array(); // 2d array of size #pts, k where each entry is (lat, long, dist) for each of the k closest points.
       $point_inv_dists = array(); // 1d array of size # pts, each value is sum of inverse dists to k closest pts.
       for($x = 0; $x < count($point_arr); $x++) {
           $lat = $point_arr[$x][2];
           $long = $point_arr[$x][3];
           $types = checkbox_format($crime_types_list)[0];
           $query = "SELECT cp.latitude, cp.longitude, SQRT(POW(ABS(($lat - cp.latitude)), 2) + POW(ABS(($long - cp.longitude)), 2)) as dist FROM crime_point cp WHERE 1=1 $types ORDER BY dist LIMIT $k"; // note I can get many pts here
           //echo $query;
           $result = mysqli_query($db, $query);
           if($result) {
               $i = 0;
               $inv_dist = 0.0;
               while($row = mysqli_fetch_row($result)) {
                  $crime_point_arr[$crime_point_id++] = array($row[0],$row[1]);
                  $inv_dist_cur = 1 / floatval($row[2]);
                  $inv_dist += $inv_dist_cur;
                  $closest_crime_points[$x][$i++] = 
                  [$row[0], $row[1], $inv_dist_cur];
               }
            //   echo "Current inv dist sum: ", strval($inv_dist), " ";
               $point_inv_dists[$x] = $inv_dist;
               $point_score_arr[$x] = $inv_dist;
           } else {
               $error = "Error: ".$sql_insert." ".mysqli_error($db);
               echo $error;
           }
           
       }
       for($i=0; $i<count($point_score_arr); $i++) {
           $point_score_arr[$i] = $c  * ($point_score_arr[$i] - min($point_score_arr)) / (max($point_score_arr) - min($point_score_arr));
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

    
    <p id="debug"></p>

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
      
        
      
       
        
        
        
        map.on('postcompose', function(event) {
            var vectorContext = event.vectorContext;
            var frameState = event.frameState;
            var i;
            
           
            
            
            
            
            
            var coordinates0 = JSON.parse( '<?php echo json_encode($point_arr) ?>' );
            
            //$point_inv_dists
            var scores0 = JSON.parse( '<?php echo json_encode($point_score_arr) ?>' );
            
            //$closest_crime_pts
            var crime_coords = JSON.parse( '<?php echo json_encode($crime_point_arr) ?>' );
            
            for(i = 0; i < scores0.length; i++)
                scores0[i] = parseFloat(scores0[i]);
            
            var maxScore = 3000; //Math.max.apply(null, scores0);
            
            <!--document.getElementById("debug").innerHTML = crime_coords.length;-->
            
            for(i = 0; i < crime_coords.length; i++)
            {
                var newPoint = ol.proj.fromLonLat([
                parseFloat(crime_coords[i][1]), 
                parseFloat(crime_coords[i][0])
                ]);
                
                
                
                
                //var cur = scores0[i] / maxScore * 255;
                
                //document.getElementById("debug").innerHTML = newPoint;
                
                var imageStyle = new ol.style.Style({
                    image: new ol.style.Circle({
                      radius: 5,
                      fill: new ol.style.Fill({color: new Array(255, 255, 0, 0.5)}),
                      stroke: new ol.style.Stroke({color: 'green', width: 1})
                    })
                });
                
                vectorContext.setStyle(imageStyle);
                vectorContext.drawGeometry(new ol.geom.Point(newPoint));
            }
            
            for(i = 0; i < coordinates0.length; i++)
            {
                var newPoint = ol.proj.fromLonLat([
                parseFloat(coordinates0[i][3]), 
                parseFloat(coordinates0[i][2])
                ]);
                
                
                
                
                var curRed = scores0[i] / maxScore * 255;
                
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
                
                /*
                if (i < coordinates0.length - 1)
                {
                    var nextPoint = ol.proj.fromLonLat([
                    parseFloat(coordinates0[i+1][3]), 
                    parseFloat(coordinates0[i+1][2])
                    ]);
                    
                    vectorContext.drawGeometry(new ol.geom.LineString([newPoint, nextPoint]));
                }*/
                
                
            }
            
            
            
            //document.getElementById("debug").innerHTML = coordinates;
            
            
            map.render();
        });
        map.render();
        
      
    </script>
    <?php
        if(count($similar_route_ids) > 0) {
            echo "<div><h3>Similar Routes:</h3>";
            foreach($similar_route_ids as $row) {
                echo "<div style=\"padding-left:10px; padding-top:5px; padding-bottom:5px; align-self: start;\"><a href=\"read_only_route_map.php?route_id=".$row[1]."\"> ".$row[0]." </a></div>";
            }
        }

            
        ?>
        </div>
    <div align="center">
    <form action="<?php $_PHP_SELF ?>" method="post")>
        <div align="left"><h3>Method of Scoring: <?php echo $type_of_scoring?></h3></div>
        <div align="left"><input type="submit" name="1-closest" value="1-Closest"/></div>
        <div align="left"><input type="submit" name="10-closest" value="10-Closest"/></div>
        <div align="left"><input type="submit" name="range" value="Range"/></div>
        <div align="left"><h3>Types of Crime: <?php echo $crime_types_list?></h3></div>
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
    </div>
    <form action="" method="post")>
        <div align="center"><input type="submit" value="Delete Route"/></div>
    </form>
   </body>
   
</html>

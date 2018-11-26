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
   
   $sql = "SELECT * FROM route WHERE route_id='$route_id'";
   $result = mysqli_query($db,$sql);
   $row = mysqli_fetch_array($result, MYSQLI_ASSOC);
   
   //Make sure route exists
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
      <h1 style="padding-left: 10px"><?php echo "VIEW_ONLY: ".$route_name; ?></h1>
      <h3 style="flex: 1; display:flex; justify-content:flex-end">
         <a style="aign-self: flex-end; padding-right: 10px" href = "welcome.php">Back</a></h2>
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
   </body>
   
</html>

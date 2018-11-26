<?php
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
    
    if($_POST["add_pt"]) {
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
    if($_POST["ins_pt"]) {
        echo "aaaa";
        echo $_POST["ins_pt"];
        $order_id = -1;
        // go through post["point_#"]'s..
        foreach($_POST as $key => $value) {
            if(substr($key, 0, 5) == "Point_") {
                $order_id = (int)substr($key, 6);
            }
        }
        $point_arr = array_slice($point_arr, 0, $order_id-1, true) +
        array($order_id => [$order_id, $route_id, 0, 0, null]) +
        array_slice($point_arr, $order_id+1, count($point_arr)-1, true);
    }
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
    
        <?php 
            foreach($point_arr as $point) {
                echo "<div><text>Order: ".$point[0]." Lat: ".$point[2]." Long: ".$point[3]."</text></div> 
                <form action=\"\" method=\"post\">
                    <div align=\"middle\"><input type=\"radio\" name=\"Point\" value=\"Point_".$point[0]."></div></form>";
            }
        ?>
        <form action="" method="post")>
            <div style="padding-left: 10px"><text>Lat: </text><input type="text" name="lat"/></div>
            <div><text>Long: </text><input type="text" name="long"/></div>
            <div><input type="submit" value="add_pt"/>Add Point</div>
            <div><input type="submit" value="ins_pt"/>Insert Point</div>
        </form>
    <?php 
        echo "Order: ".$order;
    ?>
   </body>
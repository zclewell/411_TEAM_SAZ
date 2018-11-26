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
   <link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.4.2/css/all.css" integrity="sha384-/rXc/GQVaYpyDdyxK+ecHPVYJSN9bmVFBvjA/9eOB+pb3F2w2N6fc5qB9Ew5yIns" crossorigin="anonymous">
   </head>
   
   <body bgcolor="#f2f7f9">
    <div align="center">
    <div style="background-color:#b1bfca; color:#ffffff; display:flex; align-items:center; flex: 1">
      <h1 style="padding-left: 10px">Viewing Route: <?php echo $route_name; ?></h1> 
      <h3 style="flex: 1; display:flex; justify-content:flex-end">
         <a style="aign-self: flex-end; padding-right: 10px" href = "welcome.php">Back</a></h2>
   </div>
    <div style = "font-size:11px; color:#cc0000; margin-top:10px"><?php echo $error; ?></div>
    
        <?php 
            foreach($point_arr as $point) {
                echo "<div><text>Order: ".$point[0]." Lat: ".$point[2]." Long: ".$point[3]."</text></div>";
            }
        ?>
        <form action="" method="post")>
            <div style="padding-left: 10px"><text>Lat: </text><input type="text" name="lat"/></div>
            <div><text>Long: </text><input type="text" name="long"/></div>
            <div><input type="submit" value="Add Point"/></div>
        </form>
    <?php 
        echo "Order: ".$order;
    ?>
   </body>
   
</html>
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
      <title>Route Page</title>
   </head>
   
   <body>
      <h1>Viewing Route: <?php echo $route_name; ?></h1> 
    <h2><a style="font-size:14px; margin-bottom: 10px" href = "welcome.php">Back</a></h2>
        <?php 
            foreach($point_arr as $point) {
                echo "<div><text>Order: ".$point[0]." Lat: ".$point[2]." Long: ".$point[3]."</text></div>";
            }
        ?>
    <form action="" method="post")>
        <div><input type="submit" value="Delete Route"/></div>
    </form>
    <div style = "font-size:11px; color:#cc0000; margin-top:10px"><?php echo $error; ?></div>
   </body>
   
</html>
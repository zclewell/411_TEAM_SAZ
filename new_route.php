<?php
   include('session.php');
   $username = $login_session;
   $point_arr = array();
   $point_id = 0;
  
    if($_SERVER["REQUEST_METHOD"] == "POST") {
        $route_name = mysqli_real_escape_string($db, $_POST['route_name']);
        
        $sql = "INSERT INTO route(owner, name) VALUES ('$username', '$route_name')";
        $result = mysqli_query($db, $sql);
        if($result) {
            $id = mysqli_insert_id($db);
            header("location: add_points.php?route_id=$id");
        } else {
            $error = "Error: ".$sql_insert." ".mysqli_error($db);
        }
    }
?>
<html">
   
   <head>
      <title>Chicageo</title>
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
      <h1 style="padding-left: 10px">Route Creation</h1>
      <h3 style="flex: 1; display:flex; justify-content:flex-end">
         <a style="aign-self: flex-end; padding-right: 10px" href = "welcome.php">Back</a></h2>
      </div>
      <div id="route_list" style="flex-direction:column; padding-top: 10px">
        <form action="" method="post">
            <div><text>Route Name: </text><input type="text" name="route_name"/></div>
            <div><input type="submit" value="Submit"/></div>
        </form>
       </div>
       <div style = "font-size:11px; color:#cc0000; margin-top:10px"><?php echo $error; ?></div>

       </div>
   </body>
   
</html>
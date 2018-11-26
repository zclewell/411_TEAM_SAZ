<?php
   include('session.php');
   
   $username = $login_session;
   $sql = "SELECT * FROM route WHERE owner='$username'";
   $result_set = mysqli_query($db,$sql);
   $route_arr = array();
   $i = 0;
   while($row = mysqli_fetch_row($result_set)) {
      $route_arr[$i++] = $row;
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
      <h1 style="padding-left: 10px">Welcome <?php echo $login_session; ?>!</h1>
      <h3 style="flex: 1; display:flex; justify-content:flex-end">
         <a style="aign-self: flex-end; padding-right: 10px" href = "logout.php">Sign Out</a></h2>
   </div>
   <div id="route_list" style="background-color:#ffffff; margin:10px; border-radius: 10px; width: 30%" align="left">
   <div style = "background-color:#b1bfca; color:#FFFFFF; border-top-right-radius: 10px; border-top-left-radius: 10px; padding: 10px; display:flex;">
   <b>My Routes</b>
   <div style="display: flex;flex:1;justify-content:flex-end; align-items: start"><a href = "new_route.php"><i class="fas fa-plus"></i></a></h3></div>
   </div>
   <?php
      foreach($route_arr as $route) {
          echo "<div style=\"padding-left:10px; padding-top:5px; padding-bottom:5px; align-self: start;\"><a href=\"route_map.php?route_id=".$route[0]."\"> ".$route[2]." </a></div>";
      }
      ?>
   </div>
   </div>
   </div>
</body>
</html>
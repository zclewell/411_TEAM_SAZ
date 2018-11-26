<?php
   include("cgi-bin/config.php");
   session_start();
   
   if($_SERVER["REQUEST_METHOD"] == "POST") {
      // username and password sent from form 
      
      $newusername = mysqli_real_escape_string($db,$_POST['username']);
      $newpassword = mysqli_real_escape_string($db,$_POST['password']); 

      $sql = "SELECT username FROM users WHERE username = '$newusername'";
      $result = mysqli_query($db,$sql);
      $row = mysqli_fetch_array($result,MYSQLI_ASSOC);
      $active = $row['active'];
      
      $count = mysqli_num_rows($result);
      
      // If result matched $myusername and $mypassword, table row must be 1 row
	
      if($count == 1) {
        $error = "Username already exists, please select another.";
      }else {
        $sql_insert = "INSERT INTO users VALUES('$newusername','$newpassword')";
        $result = mysqli_query($db,$sql_insert);
        if($result) {
            $_SESSION['login_user'] = $newusername;
            header("location: welcome.php");
        } else {
            $error = "Error: ".$sql_insert." ".mysqli_error($db);
        }
      }
      
   }
   
   function alert($msg) {
    echo "<script type='text/javascript'>alert('$msg');</script>";
}
?>
<html>
   
   <head>
      <title>Create User</title>
      
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
   
   <body bgcolor = "#f2f7f9">
	
      <div align = "center">
         <div style = "margin-top: 5%;width:300px; background-color:#ffffff; border-radius: 10px;" align = "left">
            <div style = "background-color:#b1bfca; color:#FFFFFF; padding:10px; border-top-right-radius: 10px; border-top-left-radius: 10px"><b>Create User</b></div>
				
            <div style = "margin:20px">
               
               <form action = "" method = "post">
                  <label>UserName  :</label><input type = "text" name = "username" class = "box"/><br /><br />
                  <label>Password  :</label><input type = "password" name = "password" class = "box" /><br/><br />
                  <input type = "submit" value = " Submit "/><br />
               </form>
               
               <div style = "font-size:11px; color:#cc0000; margin-top:10px"><?php echo $error; ?></div>

            </div>
				
         </div>
			
      </div>

   </body>
</html>
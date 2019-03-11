<?php
if ($_SESSION['status']!=1) die("Wrong script call. <a href=\"index.php\">LMever</a>");
	    $f=$_GET['id2'];
            
            $title = generate_title("Timesheet");
            $description = "LMeve Timesheet - industry contribution tracking";
            generate_meta($description, $title);
            
	    switch ($f) {
	    case 0:
		include("00.php");  //lista userow
		break;

	}
	?>

<?php

function GetIMGPosFromGTAPos($x, $y, &$gtax, &$gtay)
{
	$GTA_X = 6000.0;
	$GTA_Y = 6000.0;

    $IMG_X = GetIMGSize();
    $IMG_Y = GetIMGSize();

	// Translate X & Y
	$x = $x + ($GTA_X / 2.0);
	$y = $y + ($GTA_Y / 2.0);

	// Invert Y
	$y = ($GTA_Y - $y);

	// Scale X & Y
	$gtax = round(($x / $GTA_X) * $IMG_X);
	$gtay = round(($y / $GTA_Y) * $IMG_Y);

	return 1;
}

function DrawLineAtGTAPos($im, $g_x1, $g_y1, $g_x2, $g_y2)
{
	$Line_Thickness = 6;

    // Alloc color for CPs
	$color = imagecolorallocate($im, 155, 20, 60);

	// Draw Line

	$i_x1 = 0;
	$i_y1 = 0;
	$i_x2 = 0;
	$i_y2 = 0;

	GetIMGPosFromGTAPos($g_x1, $g_y1, $i_x1, $i_y1);
	GetIMGPosFromGTAPos($g_x2, $g_y2, $i_x2, $i_y2);

    imagesetthickness($im, $Line_Thickness);
  	imageline($im, $i_x1, $i_y1, $i_x2, $i_y2, $color);

	return 1;
}

function GetIMGSize()
{
    if(isset($_GET['imgsize']))
	{
		$img_size = $_GET['imgsize'];

		switch($img_size)
		{
		    case 500:
			case 1000:
			case 1250:
			case 2000:
		        return $img_size;
				break;
		}
	}
	
	return 500;
}

// -----------------------------------------------------------------------------

// Try connection

$user="samp";
$password="password";
$database="samp";
$verb = mysql_connect('127.0.0.1',$user,$password);
@mysql_select_db($database) or die("Unable to select database");

if(isset($_GET['race']))
{
	$race = $_GET['race'];
	
	$result = mysql_query("SELECT ID,X,Y FROM race_checkpoints WHERE RaceID=".$race." ORDER BY ID ASC;");
	
	$img_size = GetIMGSize();
	
	$array = NULL;
	$num_cps = 0;
	
	if(mysql_num_rows($result) >= 3)
	{
		// Copy results to array, we need to access 2 rows at the same time.
	
		while($tmp_obj = mysql_fetch_object($result))
		{
			if($tmp_obj->ID != 1)
			{
				$array[$num_cps] = $tmp_obj;

				$num_cps ++;
			}
		}
		
		// Loop and draw

		if($array != NULL && $num_cps >= 2)
		{
		    // Create canvas by BMP

			$im = imagecreatefromjpeg("race_map/gtasa_map_".$img_size.".jpg");
			
			// Draw lines
			
			$array[1] = $array[0]; // Slot 1 is the Spawn Pos, we don't need it!

			for($i = 2; $i < $num_cps; $i ++)
			{
   				DrawLineAtGTAPos($im, $array[$i - 1]->X, $array[$i - 1]->Y, $array[$i]->X, $array[$i]->Y);
			}
			
			// Draw CPs
			
            if($im_cp = imagecreatefrompng('race_map/gtasa_icon_cpbl.png'))
			{
				$ICON_SIZE = 14;

			    if($im_cp_n = imagescale($im_cp, $ICON_SIZE, $ICON_SIZE, IMG_NEAREST_NEIGHBOUR))
			    {
			        imagedestroy($im_cp);

			        $im_cp = $im_cp_n;
			    }

			    $color = imagecolorallocate($im_cp, 0, 0, 0);

			    imagecolortransparent($im_cp, $color);

			    $cp_x = 0;
			    $cp_y = 0;

                for($i = 2; $i < $num_cps - 1; $i ++)
				{
			    	GetIMGPosFromGTAPos($array[$i]->X, $array[$i]->Y, $cp_x, $cp_y);

			    	imagecopymerge($im, $im_cp, $cp_x - $ICON_SIZE/2, $cp_y - $ICON_SIZE/2, 0, 0, $ICON_SIZE, $ICON_SIZE, 100);
				}

			    imagedestroy($im_cp);
			}
			
			// Draw Start & End
			
			if($im_start = imagecreatefrompng('race_map/gtasa_icon_startbl.png'))
			{
				$ICON_SIZE = 60;
				
			    if($im_start_n = imagescale($im_start, $ICON_SIZE, $ICON_SIZE, IMG_NEAREST_NEIGHBOUR))
			    {
			        imagedestroy($im_start);
			        
			        $im_start = $im_start_n;
			    }
			    
			    $color = imagecolorallocate($im_start, 0, 0, 0);
			    
			    imagecolortransparent($im_start, $color);
			    
			    $start_x = 0;
			    $start_y = 0;
			    
			    GetIMGPosFromGTAPos($array[1]->X, $array[1]->Y, $start_x, $start_y);
			    
			    imagecopymerge($im, $im_start, $start_x - $ICON_SIZE/2, $start_y - $ICON_SIZE/2, 0, 0, $ICON_SIZE, $ICON_SIZE, 100);
			    
			    imagedestroy($im_start);
			}

            if($im_end = imagecreatefrompng('race_map/gtasa_icon_endbl.png'))
			{
				$ICON_SIZE = 60;

			    if($im_end_n = imagescale($im_end, $ICON_SIZE, $ICON_SIZE, IMG_NEAREST_NEIGHBOUR))
			    {
			        imagedestroy($im_end);

			        $im_end = $im_end_n;
			    }

			    $color = imagecolorallocate($im_end, 0, 0, 0);

			    imagecolortransparent($im_end, $color);

			    $end_x = 0;
			    $end_y = 0;

			    GetIMGPosFromGTAPos($array[$num_cps - 1]->X, $array[$num_cps - 1]->Y, $end_x, $end_y);

			    imagecopymerge($im, $im_end, $end_x - $ICON_SIZE/2, $end_y - $ICON_SIZE/2, 0, 0, $ICON_SIZE, $ICON_SIZE, 100);

			    imagedestroy($im_end);
			}
			
			// Set the content type header and output

			header("Content-type: image/jpeg");
			imagejpeg($im);

			// Free up memory
			
			imagedestroy($im);
		}
	}
}

?>

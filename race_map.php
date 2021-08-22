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
	
	return 1250;
}

// -----------------------------------------------------------------------------

// Try connection

$mysqli = new mysqli("localhost", "nrace_user", "FEs3xDWZUWGogUWh", "nrace");

if ($mysqli -> connect_errno)
{
  echo "Failed to connect to MySQL: " . $mysqli -> connect_error;
  exit();
}

if(isset($_GET['race']))
{
	$race = $_GET['race'];
	$draw_info = isset($_GET['drawinfo']);

	// Get Race Info

	$result = $mysqli->query("SELECT Name, Author, Type, GameVModel FROM nr_race_meta WHERE ID = ".$race.";");

	if($result->num_rows != 1)
	{
		echo "Invalid Race ID";
		exit();
	}

	$race_info = mysqli_fetch_object($result);

	// Get Race Type

	$result = $mysqli->query("SELECT Name FROM nr_race_types WHERE ID = ".$race_info->Type.";");

	if($result->num_rows != 1)
	{
		echo "Invalid Race Type";
		exit();
	}

	$race_type = mysqli_fetch_object($result);

	// Get Checkpoints

	$result = $mysqli->query("SELECT ID, PosX, PosY FROM nr_race_checkpoints WHERE RaceID = ".$race." ORDER BY ID ASC;");
	
	$img_size = GetIMGSize();
	
	$array = NULL;
	$num_cps = 0;

	// Create canvas by JPG

	$im = imagecreatefromjpeg("race_map/gtasa_map_".$img_size.".jpg");

	// Draw CPs
	
	if($result->num_rows >= 2)
	{
		// Copy results to array, we need to access 2 rows at the same time.
	
		while($tmp_obj = mysqli_fetch_object($result))
		{
			$array[$num_cps] = $tmp_obj;

			$num_cps ++;
		}

		if($array != NULL && $num_cps >= 2)
		{			
			// Draw lines
			
			for($i = 1; $i < $num_cps; $i ++)
			{
   				DrawLineAtGTAPos($im, $array[$i - 1]->PosX, $array[$i - 1]->PosY, $array[$i]->PosX, $array[$i]->PosY);
			}

			if($race_info->Type == 0)
			{
				DrawLineAtGTAPos($im, $array[0]->PosX, $array[0]->PosY, $array[$num_cps - 1]->PosX, $array[$num_cps - 1]->PosY);
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
					GetIMGPosFromGTAPos($array[$i]->PosX, $array[$i]->PosY, $cp_x, $cp_y);

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
				
				GetIMGPosFromGTAPos($array[0]->PosX, $array[0]->PosY, $start_x, $start_y);
				
				imagecopymerge($im, $im_start, $start_x - $ICON_SIZE/2, $start_y - $ICON_SIZE/2, 0, 0, $ICON_SIZE, $ICON_SIZE, 100);
				
				imagedestroy($im_start);
			}

			if($race_info->Type != 0 && $im_end = imagecreatefrompng('race_map/gtasa_icon_endbl.png'))
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

				GetIMGPosFromGTAPos($array[$num_cps - 1]->PosX, $array[$num_cps - 1]->PosY, $end_x, $end_y);

				imagecopymerge($im, $im_end, $end_x - $ICON_SIZE/2, $end_y - $ICON_SIZE/2, 0, 0, $ICON_SIZE, $ICON_SIZE, 100);

				imagedestroy($im_end);
			}
		}

		// Draw Name and Type

		if($draw_info)
		{
			$size = $img_size / 80;

			$pos_x = $img_size / 100;
			$pos_y = $img_size / 100 + $size;

			$text_col_fg = imagecolorallocate($im, 255, 255, 255);
			$text_col_bg = imagecolorallocate($im, 0, 0, 0);

			$text = "'".$race_info->Name."' (".$race_type->Name." Race)";
			$font = "font/Amiga Forever.ttf";

			$outline = $img_size / 500;

			for($x = -$outline; $x <= $outline; $x++)
			{
				for($y = -$outline; $y <= $outline; $y++)
				{
					imagettftext($im, $size, 0, $pos_x + $x, $pos_y + $y, $text_col_bg, $font, $text);
				}
			}
			
			imagettftext($im, $size, 0, $pos_x, $pos_y, $text_col_fg, $font, $text);
		}

		// Set the content type header and output

		header("Content-type: image/jpeg");
		imagejpeg($im);

		// Free up memory
		
		imagedestroy($im);
	}
}

?>

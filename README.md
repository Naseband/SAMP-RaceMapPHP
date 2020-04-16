# SAMP-RaceMapPHP

Draws a map from race checkpoints stored in a mysql table. You should only use this to generate the final images. Do not use this live, it's not really efficient!
To generate a row of images, simply loop through all race IDs and use imagejpeg() or similar.

race_map.php uses 2 parameters, the race ID and imagesize (valid image sizes are defined in the php, you also have to save a scaled copy of the SA map):

```race_map.php?race=ID&imgsize=SIZE```

Example:

![Example](https://github.com/Naseband/SAMP-RaceMapPHP/blob/master/race_map.php.jpg)

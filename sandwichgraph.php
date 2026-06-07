<?php
header('Content-Type: image/png');

// Create image
$image = imagecreatetruecolor(500, 200); // Increased width for key

// Colors
$white = imagecolorallocate($image, 255, 255, 255);
$black = imagecolorallocate($image, 0, 0, 0); // For text
$brown = imagecolorallocate($image, 165, 42, 42); // Bread
$green = imagecolorallocate($image, 0, 128, 0); // Lettuce
$red = imagecolorallocate($image, 255, 0, 0); // Tomato
$yellow = imagecolorallocate($image, 255, 255, 0); // Cheese

imagefill($image, 0, 0, $white);

// Get current time
$hours = date('G');
$minutes = date('i');
$seconds = date('s');

// Map time to layer thickness
$breadThickness = 20;
$lettuceThickness = (int)($hours / 24 * 50); // Max thickness 50px
$tomatoThickness = (int)($minutes / 60 * 30); // Max thickness 30px
$cheeseThickness = (int)($seconds / 60 * 20); // Max thickness 20px

// Draw sandwich from bottom up
$y = 200 - $breadThickness; // Bottom bread
imagefilledrectangle($image, 50, $y, 350, $y + $breadThickness, $brown);
$y -= $lettuceThickness;
imagefilledrectangle($image, 50, $y, 350, $y + $lettuceThickness, $green);
$y -= $tomatoThickness;
imagefilledrectangle($image, 50, $y, 350, $y + $tomatoThickness, $red);
$y -= $cheeseThickness;
imagefilledrectangle($image, 50, $y, 350, $y + $cheeseThickness, $yellow);
$y -= $breadThickness; // Top bread
imagefilledrectangle($image, 50, $y, 350, $y + $breadThickness, $brown);

// Add keys
$font = 5; // Built-in font size
$keysStart = 360;
imagestring($image, $font, $keysStart, 30, 'Bread', $brown);
imagestring($image, $font, $keysStart, 50, 'Lettuce', $green);
imagestring($image, $font, $keysStart, 70, 'Tomato', $red);
imagestring($image, $font, $keysStart, 90, 'Cheese', $yellow);
// Output image
imagepng($image);
imagedestroy($image);
?>

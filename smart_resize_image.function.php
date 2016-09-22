<?
 function smart_resize_image($file,
                              $width              = 0, 
                              $height             = 0, 
                              $size       = "cover", 
                              $output             = 'file', 
                              $delete_original    = true, 
                              $use_linux_commands = false ) {
      
    if ( $height <= 0 && $width <= 0 ) return false;
    # Setting defaults and meta
    $info                         = getimagesize($file);
    $image                        = '';
    $final_width                  = 0;
    $final_height                 = 0;
    list($width_old, $height_old) = $info;
    # Calculating proportionality
    if ($size=="contain") {
        
        $x_ratio = $width / $width_old;
        $y_ratio = $height / $height_old;

        if (($width_old <= $width) && ($height_old <= $height)) {
            $final_width = $width_old;
            $final_height = $height_old;
        } elseif (($x_ratio * $height_old) < $height) {
            $final_height = ceil($x_ratio * $height_old);
            $final_width = $width;
        } else {
            $final_width = ceil($y_ratio * $width_old);
            $final_height = $height;
        }
    }
    else if($size=="cover") {
        
        $original_aspect = $width_old / $height_old;
        $thumb_aspect = $width / $height;

        if ( $original_aspect >= $thumb_aspect )
        {
        // If image is wider than thumbnail (in aspect ratio sense)
        $final_height = $height;
        $final_width = $width_old / ($height_old / $height);
        }
        else
        {
        // If the thumbnail is wider than the image
        $final_width = $width;
        $final_height = $height_old / ($width_old / $width);
        }
      
    }
    # Loading image to memory according to type
    switch ( $info[2] ) {
      case IMAGETYPE_GIF:   $image = imagecreatefromgif($file);   break;
      case IMAGETYPE_JPEG:  $image = imagecreatefromjpeg($file);  break;
      case IMAGETYPE_PNG:   $image = imagecreatefrompng($file);   break;
      default: return false;
    }
    
    
    # This is the resizing/resampling/transparency-preserving magic
    $image_resized = imagecreatetruecolor( $width, $height );
    if ( ($info[2] == IMAGETYPE_GIF) || ($info[2] == IMAGETYPE_PNG) ) {
      $transparency = imagecolortransparent($image);
      if ($transparency >= 0) {
        $transparent_color  = imagecolorsforindex($image, $trnprt_indx);
        $transparency       = imagecolorallocate($image_resized, $trnprt_color['red'], $trnprt_color['green'], $trnprt_color['blue']);
        imagefill($image_resized, 0, 0, $transparency);
        imagecolortransparent($image_resized, $transparency);
      }
      elseif ($info[2] == IMAGETYPE_PNG) {
        imagealphablending($image_resized, false);
        $color = imagecolorallocatealpha($image_resized, 0, 0, 0, 127);
        imagefill($image_resized, 0, 0, $color);
        imagesavealpha($image_resized, true);
      }
    }
    imagecopyresampled($image_resized, $image, (($width - $final_width)/ 2), (($height - $final_height) / 2), 0, 0, $final_width, $final_height, $width_old, $height_old);
    
    # Taking care of original, if needed
    if ( $delete_original ) {
      if ( $use_linux_commands ) exec('rm '.$file);
      else @unlink($file);
    }
    # Preparing a method of providing result
    switch ( strtolower($output) ) {
      case 'browser':
        $mime = image_type_to_mime_type($info[2]);
        header("Content-type: $mime");
        $output = NULL;
      break;
      case 'file':
        $output = $file;
      break;
      case 'return':
        return $image_resized;
      break;
      default:
      break;
    }
    
    # Writing image according to type to the output destination
    switch ( $info[2] ) {
      case IMAGETYPE_GIF:   imagegif($image_resized, $output);    break;
      case IMAGETYPE_JPEG:  imagejpeg($image_resized, $output);   break;
      case IMAGETYPE_PNG:   imagepng($image_resized, $output);    break;
      default: return false;
    }
    return true;
  }
?>

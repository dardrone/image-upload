<?php

interface iFile
{
    public function getName();

    public function getTmpName();

    public function getError();

    public function getType();

    public function getExtension();
}

class Image implements iFile
{

    private $name;
    private $tmpName;
    private $type;
    private $error;
    private $extension;
    private $crop_x;
    private $crop_y;

    public function __construct($_FILE, $crop_x = null, $crop_y = null)
    {
        $this->name = $_FILE['name'];
        $this->tmpName = $_FILE['tmp_name'];
        $this->type = $_FILE['type'];
        $this->error = $_FILE['error'];
        $this->extension = pathinfo($_FILE['name'], PATHINFO_EXTENSION);
        $this->crop_x = $crop_x;
        $this->crop_y = $crop_y;
    }

    public function getName()
    {
        return $this->name;
    }

    public function getTmpName()
    {
        return $this->tmpName;
    }

    public function getError()
    {
        return $this->error;
    }

    public function getType()
    {
        return $this->type;
    }

    public function getExtension()
    {
        return $this->extension;
    }

    public function getCropX()
    {
        return $this->crop_x;
    }

    public function getCropY()
    {
        return $this->crop_y;
    }
}

/**
 * @param string $type
 * @return bool
 */
function isImage($type)
{
    return in_array($type, ['image/png', 'image/jpeg']);
}

/**
 * Uploads multiple copies of an image and
 * places them in their new home.
 * @param Image $image
 * @param $directory
 * @return string
 * @throws Exception
 */
function uploadImages(Image $image, $directory)
{
    if ($image->getError() == UPLOAD_ERR_OK) {
        uploadImageToDirectoryAsJPEG($image, $directory, 900, 900);
        uploadImageToDirectoryAsJPEG($image, $directory, 500, 500);
        uploadImageToDirectoryAsJPEG($image, $directory, 180, 180);
        return 'All images have finished uploading!';
    } else {
        throw new Exception("There was an error with your upload!");
    }
}

/**
 * Uploads an image to a directory as a JPEG.
 * @param Image $image
 * @param $directory
 * @param $w
 * @param $h
 * @return string
 * @throws Exception
 */
function uploadImageToDirectoryAsJPEG(Image $image, $directory, $w, $h)
{
    $dst_img = convertToTrueColorAndResizeImage($image, $w, $h);
    $newImageName = rtrim(basename($image->getName(), $image->getExtension()), '.') . '_' . $w . '.jpg';
    imagejpeg($dst_img, $directory . $newImageName, 90);
    imagedestroy($dst_img);
}

/**
 * @param Image $image
 * @param $w
 * @param $h
 * @return resource
 * @throws Exception
 */
function convertToTrueColorAndResizeImage(Image $image, $w, $h)
{
    $path = $image->getTmpName();

    if ($image->getType() == 'image/png') {
        $sourceImage = imagecreatefrompng($path);
    } elseif ($image->getType() == 'image/jpeg') {
        $sourceImage = imagecreatefromjpeg($path);
    } else {
        throw new Exception("Image is not in preferred format.");
    }

    /* Get size of src image so and use crop
       coordinates if we have them. */
    list($width, $height) = getimagesize($path);
    if(!is_null($image->getCropX()) && !is_null($image->getCropY())){
        list($width, $height) = array($image->getCropX(), $image->getCropY());
    }

    $dstImage = imagecreatetruecolor($w, $h);

    /* Preserve transparency for .png */
    if($image->getType() == 'image/png') {
        imagecolortransparent($dstImage, imagecolorallocatealpha($dstImage, 0, 0, 0, 127));
        imagealphablending($dstImage, false);
        imagesavealpha($dstImage, true);
    }

    imagecopyresampled($dstImage, $sourceImage, 0, 0, 0, 0, $w, $h, $width, $height);
    return $dstImage;
}

/**
 * Start here.
 */
function main()
{
    $uploads_dir = 'images/';

    /** e.g. imageUpload.php?c_x=1200&c_y=1200 */
    foreach ($_FILES as $file) {
        if (isImage($file['type'])) {
            $image = new Image($file, $_GET['c_x'], $_GET['c_y']);
            return uploadImages($image, $uploads_dir);
        } else {
            return 'Sorry, I only accept png\'s and jpegs!';
        }
    }
}

echo main();

?>
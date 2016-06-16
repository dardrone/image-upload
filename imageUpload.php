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

    public function __construct()
    {
        $a = func_get_args();
        $i = func_num_args();
        if (method_exists($this, $f = '__construct' . $i)) {
            call_user_func_array(array($this, $f), $a);
        }
    }

    public function __construct1($_FILE)
    {
        $this->name = $_FILE['name'];
        $this->tmpName = $_FILE['tmp_name'];
        $this->type = $_FILE['type'];
        $this->error = $_FILE['error'];
        $this->extension = pathinfo($_FILE['name'], PATHINFO_EXTENSION);
    }

    public function __construct4($name, $tmp_name, $type, $error)
    {
        $this->name = $name;
        $this->tmpName = $tmp_name;
        $this->type = $type;
        $this->error = $error;
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
    $newImageName = rtrim(basename($image->getName(), $image->getExtension()),'.') . '_'. $w . '.jpg';
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

    if ($image->getType() === 'image/png') {
        $sourceImage = imagecreatefrompng($path);
    } elseif ($image->getType() === 'image/jpeg') {
        $sourceImage = imagecreatefromjpeg($path);
    } else {
        throw new Exception("Image is not in preferred format.");
    }

    list($width, $height) = getimagesize($path);
    $dstImage = imagecreatetruecolor($w, $h);
    imagecopyresampled($dstImage, $sourceImage, 0, 0, 0, 0, $w, $h, $width, $height);
    return $dstImage;
}

/**
 * Start here.
 */
function main()
{
    $uploads_dir = 'images/';

    foreach ($_FILES as $file) {
        if (isImage($file['type'])) {
            $image = new Image($file);
            return uploadImages($image, $uploads_dir);
        } else {
            return 'Sorry, I only accept png\'s and jpegs!';
        }
    }
}

echo main();

?>
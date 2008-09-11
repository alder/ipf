<?

class IPF_Image_Thumbnail {
    
    protected $Source, $Thumbnail;
    protected $ThumbnailWidth, $ThumbnailHeight;
    protected $SourceWidth, $SourceHeight, $SourceType;
    protected $file_permission, $dir_permission;
        
    public function __construct($source, $thumnbail, $width=null, $height=null, $dir_permission=null, $file_permission=null){
        $this->Source = $source;
        $this->Thumbnail = $thumbnail;

        if (($width==null) && ($height==null))
            throw new IPF_Exception_Image(__('Please Specify width or height'));

        $this->ThumbnailWidth = $width;
        $this->ThumbnailHeight = $height;
        
        if ($dir_permission)
            $this->dir_permission = $dir_permission;
        else
            $this->dir_permission = IPF::get('dir_permission');

        if ($file_permission)
            $this->file_permission = $file_permission;
        else
            $this->file_permission = IPF::get('file_permission');
    }
        
    public function execute(){
        $ImageInfo = getimagesize($this->Source);
        if(!$ImageInfo)
            throw new IPF_Exception_Image(sprintf(__('Cannot open %s image file'), $this->Source));
            
        $this->SourceWidth = $ImageInfo[0];
        $this->SourceHeight = $ImageInfo[1];
        $this->SourceType = $ImageInfo[2];
    
        if($this->SourceType==IMAGETYPE_JPEG)
            $im = ImageCreateFromJPEG($this->Source);
        else if($this->SourceType==IMAGETYPE_GIF)
            $im = ImageCreateFromGIF($this->Source);
        else if($this->SourceType==IMAGETYPE_PNG)
            $im = ImageCreateFromPNG($this->Source);
        else
            throw new IPF_Exception_Image(sprintf(__('Unknown image format %s'), $this->Source));

        if($this->ThumbnailWidth)
            $c1 = $this->SourceWidth/abs($this->ThumbnailWidth);
        else
            $c1 = 0;
      
        if($this->ThumbnailHeight)
            $c2 = $this->SourceHeight/abs($this->ThumbnailHeight);
        else
            $c2 = 0;
      
        $c = $c1>$c2 ? $c1 : $c2;
      
        if($c<=1){
            $this->ThumbnailWidth = $this->SourceWidth;
            $this->ThumbnailHeight = $this->SourceHeight;
            if($this->Source<>$this->Thumbnail)
                if (!@copy($this->Source, $this->Thumbnail))
                    throw new IPF_Exception_Image(sprintf(__('Cannot copy %s to %s'), $this->Source, $this->Thumbnail));
        }
        else{
            if($this->ThumbnailWidth<0 and $this->SourceWidth/$c<(-$this->ThumbnailWidth))
              $c = $this->SourceWidth/(-$this->ThumbnailWidth);
            if($this->ThumbnailHeight<0 and $this->SourceHeight/$c<(-$this->ThumbnailHeight))
              $c = $this->SourceHeight/(-$this->ThumbnailHeight);
            
            $this->ThumbnailWidth = $this->SourceWidth/$c;
            $this->ThumbnailHeight = $this->SourceHeight/$c;
        
            $tn = imagecreatetruecolor($this->ThumbnailWidth, $this->ThumbnailHeight);
            imagecopyresampled(
                $tn, $im, 0, 0, 0, 0, 
                $this->ThumbnailWidth, $this->ThumbnailHeight, 
                $this->SourceWidth, $this->SourceHeight
            );

            if (!@unlink($this->Thumbnail))
                throw new IPF_Exception_Image(sprintf(__('Cannot delete %s'), $this->Thumbnail));

            $dir_thumbnail = dirName($this->Thumbnail);
            if (!IPF_Utils::makeDirectories(dirName($this->Thumbnail), $this->dir_permission))
                throw new IPF_Exception_Image(sprintf(__('Cannot create path %s'), $dir_thumbnail));
            
            if($this->SourceType==IMAGETYPE_JPEG){
                if (!ImageJPEG($tn, $this->Thumbnail))
                    throw new IPF_Exception_Image(sprintf(__('Cannot create JPEG %s'), $this->Thumbnail));
            }
            else if($this->SourceType==IMAGETYPE_GIF){
                if (!ImageGIF($tn, $this->Thumbnail))
                    throw new IPF_Exception_Image(sprintf(__('Cannot create GIF %s'), $this->Thumbnail));
            }
            else if($this->SourceType==IMAGETYPE_PNG){
                if (!ImagePNG($tn, $this->Thumbnail))
                    throw new IPF_Exception_Image(sprintf(__('Cannot create PNG %s'), $this->Thumbnail));
            }
            else
                throw new IPF_Exception_Image(sprintf(__('Unknown image format %s'), $this->Source));

            if (!@chmod($this->Thumbnail, $this->file_permission))
                throw new IPF_Exception_Image(sprintf(__('Cannot change permission %s'), $this->Thumbnail));
        }
    }
}
?>

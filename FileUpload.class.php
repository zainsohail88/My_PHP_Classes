<?php
/*
 * Name    : File Upload Class
 * Version : 1.0 (dev)
 * Author  : Zain Baloch
 * Website : http://zainbaloch.com
*/

class FileUpload {

	var $allowedExtensions 	= ""; // extentions saperated with commas ","
	var $MaxSize 			= ""; // allowed maximun size
	var $MinSize 			= ""; // allowed minimum size
	var $FileName 			= ""; // name of the uploaded file
	var $Path 				= ""; // save path for uploaded file 
	var $File 				= ""; // only $_FILE['file_name'];
	var $Width 				= ""; // width if uploading image
	var $Height 			= ""; // height if uploading image
	var $status 			= ""; // true=success, false=failure
	var $error 				= ""; // error description
	var $err 				= "";
	var $ext 				= "";
	var $pathupload 		= "";

	/* constructor */
	function FileUpload() {
	}

	/* validator */
	function check() {
		
		if ($this->File == "" || $this->File['tmp_name'] == "" || empty($this->File)) {
			$this->oops("Please provide a valid file");
		}
		
		if ($this->err != 1) {
			if ($this->allowedExtensions != "") {
				$this->checkAllowed();
			}
		}
		
		if ($this->err != 1) {
			if ($this->MaxSize != "") {
				$this->checkSize();
			}
		}
		
		if ($this->err != 1) {
			$this->checkPath();
		}
		
	}
	
	/* file uploader */
	function Upload() {
		$this->check();
		
		if ($this->err != 1) {
			$this->uploadFile2();
		}
	}
	
	/* image uploader */
	function IMGUpload() {
		$this->check();
		
		if ($this->err != 1) {
			$this->uploadFile();
		}
	}
	
	/* check if file extension is allowed to be uploaded */
	function checkAllowed() {
		if (!$this->Allowed()) {
			$this->oops("File extention not allowed");
		}
	}
	
	function Allowed() {
		$allowed = explode(",",$this->allowedExtensions);
		$ext = $this->Ext($this->File);
		
		if (!in_array($ext,$allowed)) {
		   return false;
		} else {
			return true;
		}
	}
	
	function Ext($file) {
		$ext = end(explode(".",strtolower($file['name'])));
		$this->ext = $ext;
		return $ext;
	}
	
	############################################
	#################File Size##################
	############################################ 
	
	function convertSize($size) {
		$siz = strtolower(substr($size,strlen($size)-1,1));
		$num = substr($size,0,strlen($size)-1);
		if (is_numeric($num)) {
			if ($siz == "k") {
				return $num*1000;
			} else if ($siz == "m") {
				return $num*1024000;
			} else if ($siz == "g") {
				return $num*1049000000;
			} else {
				$this->oops("Invalid file size");
			}
		} else {
			$this->oops("Invalid file size");
		}
	}
	
	function checkSize() {
		$size = $this->File['size'];
		
		if ($this->MaxSize != "") {
			$max = $this->convertSize($this->MaxSize);
			if ($size > $max) {
				$this->oops("You can't upload file greater then ".$this->MaxSize);
			}
		}
		if ($this->MinSize != "") {
			$min = $this->convertSize($this->MinSize);
			if ($size < $min) {
				$this->oops("You can't upload file less then ".$this->MinSize);
			}
		}
	}
	
	############################################
	################## Path ####################
	############################################ 
	
	function checkPath() {
		if ($this->Path != "") {
			if (!is_dir($this->Path)) {
				$this->createPath();
			}
			$this->pathupload = $this->Path;
		} else {
			$this->oops("Please provide a path");
		}
	}
	
	function createPath() {
		if (!mkdir($this->Path,0777)) {
			$this->oops("Unable to create path");
		}
	}
	
	############################################
	################ Upload ####################
	############################################
	
	//upload images with given width or height or both
	function uploadFile() {
		
		$ext = $this->Ext($this->File);
		
		if ($this->FileName != "") {
			$file_name = $this->FileName.".".$ext;
		} else {
			$file_name = $this->File['name'];
		}
		
		$imgdim = $this->imageWidthHeight();
		
		if ($ext=="jpg" || $ext=="jpeg" ) {
			$src = imagecreatefromjpeg($this->File['tmp_name']);
		} else if($ext=="png") {
			$src = imagecreatefrompng($this->File['tmp_name']);
		} else if ($ext=="gif") {
			$src = imagecreatefromgif($this->File['tmp_name']);
		}
		
		$tmp=imagecreatetruecolor($imgdim['W'],$imgdim['H']);
		
		$black = imagecolorallocate($tmp, 0, 0, 0);

		// Make the background transparent
		imagecolortransparent($tmp, $black);
		
		imagecopyresampled($tmp,$src,0,0,0,0,$imgdim['W'],$imgdim['H'],$imgdim['OW'],$imgdim['OH']);
		
		if($ext=="png") {
			$imgStatus = imagepng($tmp,$this->pathupload."/".$file_name,5);
		} else if ($ext=="gif") {
			$imgStatus = imagegif($tmp,$this->pathupload."/".$file_name);
		} else {
			$imgStatus = imagejpeg($tmp,$this->pathupload."/".$file_name,100);
		}
		
		if ($imgStatus) {
			$this->oops("File uploaded successfully");
			$this->status = true;
		} else {
			$this->oops("Unable to upload file");
		}
		
		imagedestroy($src);
		imagedestroy($tmp);
		
	}
	
	//upload files and images [$file->Width & $file->Heigh wont work]
	function uploadFile2() {
		
		$ext = $this->Ext($this->File);
		
		if ($this->FileName != "") {
			$file_name = $this->FileName.".".$ext;
		} else {
			$file_name = $this->File['name'];
		}
		
		if (move_uploaded_file($this->File['tmp_name'], $this->pathupload."/".$file_name)) {
			$this->oops("File uploaded successfully");
			$this->status = true;
		} else {
			$this->oops("Unable to upload file");
		}
	}
	
	function imageWidthHeight() {
		$imgdim = array();
		list($width,$height)=getimagesize($this->File['tmp_name']);
		if ($this->Width != "" && $this->Height != "") {
			$W = $this->Width;
			$H = $this->Height;
		} else if($this->Width == "" && $this->Height == "")  {
			$W = $width;
			$H = $height;
		} else if ($this->Width != "" && $this->Height == "") {
			$W = $this->Width;
			$H = ($height/$width)*$this->Width;
		} else if ($this->Width == "" && $this->Height != "") {
			$H = $this->Height;
			$W = ($width/$height)*$this->Height;
		}
		
		$imgdim['W'] 	= $W;
		$imgdim['H'] 	= $H;
		$imgdim['OW'] 	= $width;
		$imgdim['OH'] 	= $height;
		return $imgdim;
	}
	
	############################################
	
	function oops($msg) {
		$this->error	= $msg;
		$this->err		= 1;
		$this->status 	= false;
	}
	
	############################################
}
?>
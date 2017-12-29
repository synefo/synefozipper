<?php 
header('Content-type: application/json');
$folderName = uniqid();
$zipFileName = $folderName . '.zip';

$zipStr = file_get_contents('php://input');

if(!$zipStr){
	http_response_code(400);
	return;
}



//$basedata =  base64_decode($zipStr);
$f = fopen ($zipFileName, "a+");
fwrite($f, $zipStr);
fclose($f);

$zip = new ZipArchive(); 
  
if ($zip->open($zipFileName, ZipArchive::CREATE)!== TRUE) {
	exit("Impossible d'ouvrir le fichier <$zipFileName>\n");
}

   
$zip->extractTo($folderName);
$zip->close();

$ignoreMetaXml = isset($_GET['ignoreMetaXml']) && filter_var($_GET['ignoreMetaXml'], FILTER_VALIDATE_BOOLEAN);

if(isset($_GET['hierarchy']) && filter_var($_GET['hierarchy'], FILTER_VALIDATE_BOOLEAN)){
	
	$zipContent = new FileItemRecursive($folderName, $ignoreMetaXml);
	
}
else{
	
	$files = new RecursiveIteratorIterator( new RecursiveDirectoryIterator($folderName, FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::CHILD_FIRST); 

	$zipContent = [];
	foreach($files as $file){
		
		if($file->isFile()){
			if(includeFile($ignoreMetaXml, $file->getFilename()))
				array_push($zipContent, new FileItem($file, $folderName));	
		}
		else
			rmdir($file->getRealpath());
	}
	
	rmdir($folderName);
}

echo json_encode($zipContent);

unlink($zipFileName);

function includeFile($ignoreMetaXml, $fileName){
	return !$ignoreMetaXml || ($fileName != 'package.xml' && !strstr($fileName, '-meta.xml'));
}

class FileItem{
	
	public $name;
	public $data;
	public $folder;
	public $extension;
	
	public function FileItem($file, $baseFolder){
		
		$this->name = $file->getFilename();
		$this->data = base64_encode(file_get_contents($file->getRealpath()));
		$this->folder = str_replace($baseFolder, '', $file->getPath());
		$this->extension = $file->getExtension();
		unlink($file->getRealpath());
	}
}


class FileItemRecursive{
	
	public $type;
	public $name;
	public $children;
	public $data;
	public $extension;
	
	public function FileItemRecursive($extractPath, $ignoreMetaXml){
	
		if(gettype($extractPath) == 'string'){
			$this->name = $extractPath;
			$this->type = 'dir';
			$this->children = [];
			$files = new FilesystemIterator($extractPath);
			
			foreach ($files as $fileInfo){
				if($fileInfo->isDir())
					array_push($this->children, new FileItemRecursive($extractPath . '/' . $fileInfo->getFilename()));
				else{
					if(includeFile($ignoreMetaXml, $file->getFilename())) 
						array_push($this->children, new FileItemRecursive($fileInfo));
				}
			}
			rmdir($extractPath);
		}
		else{
			$this->type = 'file';
			$this->name = $extractPath->getFilename();
			$this->data = base64_encode(file_get_contents($extractPath->getRealpath()));
			$this->extension = $extractPath->getExtension();
			unlink($extractPath->getRealpath());
		}
			
	}
}


?>

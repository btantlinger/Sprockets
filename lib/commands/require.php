<?php
/**
 * Require
 *
 * @package Sprockets
 *
 * @subpackage commands
 */
class SprocketsCommandRequire extends SprocketsCommand {

	/**
	 * Command Exec
	 *
	 * @param string $param
	 * @param string $context
	 *
	 * @return string Parsed file source
	 */
	public function exec($param, $context) {
		$source = '';

		// parse require params
		if (preg_match('/\"([^\"]+)\" ([^\n]+)|\"([^\"]+)\"/', $param, $match)) { // "param"
			if (count($match) == 3) {
				$paramArg = $match[1];
				$optionArg = $match[2];
			}
			if (count($match) == 4) {
				$paramArg = $match[3];
			}

			$fileName = $this->getFileName($context, $paramArg);
			$fileContext = $this->getFileContext($context, $paramArg);

			if (	// avoid self-require
				$this->_getFilePathFromContextAndCommandParam($fileContext, $fileName)
				!=
				$this->Sprockets->getCurrentScope()
			) {
				$source = $this->Sprockets->parseFile($fileName, $fileContext);
			}

			// apply file options
			if (!empty($source) && isset($optionArg)) {
				$fileOptions = array_map('trim', explode(',', $optionArg));
				foreach ($fileOptions as $option) {
					$optionMethod = 'option'.ucfirst($option);
					$source = $this->{$optionMethod}($source, $fileContext, $fileName);
				}
			}
		} else if(preg_match('/\<([^\>]+)\>/', $param, $match)) { // <param>
			$fileName = $this->getFileName($context, $match[1]);
			$fileContext = $this->Sprockets->baseFolder;
			$source = $this->Sprockets->parseFile($fileName, $fileContext);
		}
		return $source;
	}
        
        public function optionFixpaths($source, $context = null, $filename = null) {
           if ($this->Sprockets->fileExt == 'css') {
                $conv = new CssUriHandler($context, $this->Sprockets->baseUri, $this->Sprockets->filePath);
                $source = preg_replace_callback("/url\s*\((.*)\)/siU", array($conv, "convert"), $source, false);
            }
            return $source;
        }
        
        public function optionInline_imgs($source, $context = null, $filename = null) {
           if ($this->Sprockets->fileExt == 'css') {
                $conv = new CssUriHandler($context, $this->Sprockets->baseUri, $this->Sprockets->filePath);
                $source = preg_replace_callback("/url\s*\((.*)\)/siU", array($conv, "convert"), $source, false);
            }
            return $source;
        }
                

	/**
	 * Apply minification if possible
	 *
	 * @param string $source
	 *
	 * @return string
	 */
	public function optionMinify($source, $context = null, $filename = null) {
		if ($this->Sprockets->fileExt == 'css') {
			if (!class_exists('cssmin')) {
				require_once realpath(dirname(__FILE__).'/../third-party/'.MINIFY_CSS);
			}
			$source = cssmin::minify($source, "");
		}

		if ($this->Sprockets->fileExt == 'js') {
			if (!class_exists('JSMin')) {
				require_once realpath(dirname(__FILE__).'/../third-party/'.MINIFY_JS);
			}
                        
			$source = JSMin::minify($source);
		}
		return $source;
	}

	protected function _getFilePathFromContextAndCommandParam($context, $param) {
		$contextPlusParam = $context .	'/'. str_replace(array('"','<','>'), '', $param);
		$fileDir = substr($contextPlusParam,	0, strrpos($contextPlusParam, '/'));
		$fileName = substr($contextPlusParam,	strrpos($contextPlusParam, '/')+1);

		return realpath($fileDir) . '/' . $fileName;
	}
        
        
}

class CssUriHandler {
    private $context; 
    private $validPathStarts;
    private $baseUri;
    private $filePath;
    private $inlineImgs;


    public function __construct($context, $base, $ap, $inlineImgs=false) {
        $this->context = trim($context);  
        $this->baseUri = trim($base);
        $this->filePath = dirname(trim($ap));
        $this->inlineImgs = $inlineImgs;
        
        if(!$this->endsWith($this->context, "/")) {
            $this->context = $this->context . "/";
        }
        if(!$this->endsWith($this->baseUri, "/")) {
            $this->baseUri = $this->baseUri . "/";
        }
        if(!$this->endsWith($this->filePath, "/")) {
            $this->filePath = $this->filePath . "/";
        }
        
        $this->validPathStarts = array("http://", "https://", "/", $this->context);

    }
    
    public function convert($match) {
        $path = trim(str_replace(array("'", '"'), "", $match[1]));
        if($this->canConvert($path)) {
            if($this->inlineImgs) {
                $tmp = $this->base64_encode_image(realpath($this->context) . "/" . $path);
                if($tmp !== false) {
                    $path = $tmp;
                }
            } else {
                $path = $this->str_replace_first($this->filePath, "", $this->context) . $path;
            }
        }
        return "url(" . $path . ")";      
    }
    
    
    function base64_encode_image ($imagefile) {        
        if(file_exists($imagefile)) {
            $filename = htmlentities($imagefile);
            $filetype = pathinfo($filename, PATHINFO_EXTENSION);
            $imgtype = array('jpg', 'gif', 'png');
            if (in_array($filetype, $imgtype)){
                $imgbinary = fread(fopen($filename, "r"), filesize($filename));
            } 
            return 'data:image/' . $filetype . ';base64,' . base64_encode($imgbinary);
        }        
        return false;
    }
    
    
    
    function str_replace_first($search, $replace, $subject) {
        return implode($replace, explode($search, $subject, 2));
    }

    
    private function canConvert($path) {
        foreach($this->validPathStarts as $p) {
            if($this->startsWith($path, $p)) {
                return false;
            }
        }        
        return true;
    }
    
    
    private function startsWith($haystack, $needle) {
        return (strcmp(substr($haystack, 0, strlen($needle)), $needle) === 0);

    }

    private function endsWith($haystack, $needle) {
        return (strcmp(substr($haystack, strlen($haystack) - strlen($needle)), $needle) === 0);
    }
}
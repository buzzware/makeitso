<?phpclass MakeItHow extends MakeItHowBase {	var $shape = 'square';	function main() {		echo("main");	}	function build() {		echo('colour: '.$this->colour);		echo('shape: '.$this->shape);		echo('size: '.$this->size);	}	function dir() {		if (isWindows()) {			$cmd = escapeshellcmd("dir " . $this->path);		} else {			$cmd = escapeshellcmd("ls -la " . $this->path);				}		exec($cmd,$result,$retcode);		print_r($result);	}	}?>
<?php

class MakeItHow extends MakeItHowBase {

	var $shape = 'square';

	function main() {
		echo("main");
	}

	function build() {
		echo('colour: '.$this->colour);
		echo('shape: '.$this->shape);
		echo('size: '.$this->size);
	}
	
}
?>

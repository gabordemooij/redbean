<?php

class RedUNIT_Blackhole_Misc extends RedUNIT_Blackhole {
	
	public function run() {
		
		R::debug(1);
		pass();
		R::debug(0);
		pass();
				
	}
	
}
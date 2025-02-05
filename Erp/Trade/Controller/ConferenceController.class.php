<?php
	namespace Trade\Controller;
	use Think\Controller;
	use Trade\Controller\DateFileTemplateController;

	class ConferenceController extends DateFileTemplateController 
	{
		function _initialize(){
			$powercat_id = 110;
			parent::_initialize($powercat_id);
		}
	}
?>
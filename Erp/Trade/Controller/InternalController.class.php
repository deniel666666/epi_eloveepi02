<?php
	namespace Trade\Controller;
	use Think\Controller;
	use Trade\Controller\DateFileTemplateController;

	class InternalController extends DateFileTemplateController 
	{
		function _initialize(){
			$powercat_id = 90;
			parent::_initialize($powercat_id);
		}
	}
?>
<?php
	namespace Trade\Controller;
	use Think\Controller;
	use Trade\Controller\DateFileTemplateController;

	class ExternalController extends DateFileTemplateController 
	{
		function _initialize(){
			$powercat_id = 91;
			parent::_initialize($powercat_id);
		}
	}
?>
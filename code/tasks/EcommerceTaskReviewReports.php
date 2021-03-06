<?php

/**
 *
 *
 *
 * @authors: Nicolaas [at] Sunny Side Up .co.nz
 * @package: ecommerce
 * @sub-package: tasks
 **/

class EcommerceTaskReviewReports extends BuildTask{

	/**
	 * Standard (required) SS variable for BuildTasks
	 * @var String
	 */
	protected $title = "Review E-commerce Pages using the Reports interface";

	/**
	 * Standard (required) SS variable for BuildTasks
	 * @var String
	 */
	protected $description = "
		Review a bunch of reports that provide information on the e-commerce pages created, such as the Products without Images.";

	function run($request){
		DB::alteration_message("<h1><a href=\"/admin/reports/\" target=\"_blank\">Open Reports Interface</a></h1>");
	}

}


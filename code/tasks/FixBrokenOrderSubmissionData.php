<?php

/**
 * After a bug in the saving of orders in the CMS
 * This "fixer"  was introduced to fix older orders
 * without a submission record.
 *
 */


class FixBrokenOrderSubmissionData extends BuildTask{

	protected $title = "Fixes broken order submission links";

	protected $description = "Fixes broken order submission links";

	function run($request){
		$problem = DB::query("SELECT COUNT(OrderStatusLog.ID) FROM OrderStatusLog_Submitted INNER JOIN OrderStatusLog ON OrderStatusLog_Submitted.ID = OrderStatusLog.ID WHERE OrderID = 0");
		if($problem->value()) {
			DB::alteration_message("the size of the problem is: ".$problem->value(), "deleted");
		}
		$rows = DB::query("Select ID from \"Order\" WHERE StatusID > 1");
		if($rows) {
			foreach($rows as $row) {
				$orderID = $row["ID"];
				$inners = DB::query("SELECT COUNT(OrderStatusLog.ID) FROM OrderStatusLog_Submitted INNER JOIN OrderStatusLog ON OrderStatusLog_Submitted.ID = OrderStatusLog.ID WHERE OrderID = $orderID");
				if($inners->value() < 1) {
					DB::alteration_message( "no record for order: ".$orderID, "edited");
					$sql = "
					SELECT *
					FROM OrderStatusLog_Submitted
					WHERE
						\"OrderAsString\" LIKE '%s:7:\"OrderID\";i:".$orderID."%'
						OR \"OrderAsHTML\" LIKE '%Order #".$orderID."%'

					LIMIT 1";
					if($innerInners = DB::query($sql)) {
						foreach($innerInners as $innerInnerRow) {
							DB::alteration_message( "FOUND ".$innerInnerRow["ID"], "created");
							DB::query("UPDATE OrderStatusLog SET OrderID = $orderID WHERE OrderStatusLog.ID = ".$innerInnerRow["ID"]." AND OrderID < 1");
						}
					}
				}
			}
		}
	}

}
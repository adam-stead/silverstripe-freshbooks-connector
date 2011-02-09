<?php
/**
 * Functions for the getting of data from freshbooks
 * 
 * @package freshbooksconnector
 */

class FreshBooksConnector {
	
	protected static $Domain = "";
	protected static $AuthToken = "";
	
	/*
	* Will return client list from Freshbooks based on following input
	*
	*/
	public function clientList($EmailAdd=NULL, $Username=NULL, $DateStart=NULL, $DateFinish=NULL, $Folder='active', $ItemsPerPage=100, $Notes=NULL){

		$xml_data = '<?xml version="1.0" encoding="utf-8"?>';
		$xml_data .= '<request method="client.list">';
		
		if(!is_null($EmailAdd)){$xml_data .= "<email>{$EmailAdd}</email>";}
		if(!is_null($Username)){$xml_data .= "<username>{$Username}</username>";}
		if(!is_null($DateStart)){$xml_data .= "<updated_from>{$DateStart}</updated_from>";}
		if(!is_null($DateFinish)){$xml_data .= "<updated_to>{$DateFinish}</updated_to>";}
		$xml_data .= "<per_page>{$ItemsPerPage}</per_page>";
		$xml_data .= "<folder>{$Folder}</folder>";
		if(!is_null($Notes)){$xml_data .= "<notes>{$Notes}</notes>";}
		$xml_data .= '</request>';
		
		
		return self::sendRequest($xml_data);
	
	}	
	
	public function client($id){
		if(isset($id)){
			$xml_data = '<?xml version="1.0" encoding="utf-8"?>';
			$xml_data .= '<request method="client.get">';
			$xml_data .= "<client_id>{$id}</client_id>";
			$xml_data .= '</request>';
			
			return self::sendRequest($xml_data);
		} else {
			return "No Client ID defined";
		}
	}
	
	public function deleteClient($id){
		if(isset($id)){
			$xml_data = '<?xml version="1.0" encoding="utf-8"?>';
			$xml_data .= '<request method="client.delete">';
			$xml_data .= "<client_id>{$id}</client_id>";
			$xml_data .= '</request>';
			
			$response = self::sendRequest($xml_data);
			return $response->response['status'];
			
		}  else {
			return "No Client ID defined";
		}
	}
	
	public function completeInvoiceList($ClientID=NULL, $RecurringID=NULL, $Status='unpaid', $DateStart=NULL, $DateFinish=NULL, $updated_from=NULL, $updated_to=NULL, $Folder='active', $Notes=NULL){
		$xml = self::invoiceListPaged($ClientID, $RecurringID, $Status, $DateStart, $DateFinish, $updated_from, $updated_to,1,25, $Folder);
		foreach($xml->invoices as $invoices)
		{
			if($invoices["pages"]>1){
				$numPages = $invoices["pages"];
				for ($i=1; $i<=$numPages; $i++)
				{
					$xml2 = self::invoiceListPaged($ClientID, $RecurringID, $Status, $DateStart, $DateFinish, $updated_from, $updated_to,$i,25, $Folder, $Notes);
					$listMaster = dom_import_simplexml($xml);
					$newlist  = dom_import_simplexml($xml2);
					$newlist  = $listMaster->ownerDocument->importNode($newlist, TRUE);
					$listMaster->appendChild($newlist);
				}
				return $xml;
			} else {
				return $xml;	
			}
		}
	}
	
	/*
	*
	* Retirve list of invoices from freshbooks based on optional 
	* The value used for 'status' can be 'disputed', 'draft', 'sent', 'viewed', 'paid', 'auto-paid', 'retry', 'failed' or the special status 'unpaid' which will retrieve all invoices with a status of 'disputed', 'sent', 'viewed', 'retry' or 'failed'.
	*/
	public function invoiceListPaged($ClientID=NULL, $RecurringID=NULL, $Status='unpaid', $DateStart=NULL, $DateFinish=NULL, $updated_from=NULL, $updated_to=NULL, $Page=1, $ItemsPerPage=100,  $Folder='active', $Notes=NULL){
		$xml_data = '<?xml version="1.0" encoding="utf-8"?>';
		$xml_data .= '<request method="invoice.list">';
		
		if(!is_null($ClientID)){$xml_data .= "<client_id>{$ClientID}</client_id>";}
		if(!is_null($RecurringID)){$xml_data .= "<recurring_id>{$RecurringID}</recurring_id>";}
		if(!is_null($Status)){$xml_data .= "<status>{$Status}</status>";}
		if(!is_null($DateStart)){$xml_data .= "<date_from>{$DateStart}</date_from>";}
		if(!is_null($DateFinish)){$xml_data .= "<date_to>{$DateFinish}</date_to>";}
		if(!is_null($updated_from)){$xml_data .= "<updated_from>{$updated_from}</updated_from>";}
		if(!is_null($updated_to)){$xml_data .= "<updated_to>{$updated_to}</updated_to>";}
		$xml_data .= "<page>{$Page}</page>";   
		$xml_data .= "<per_page>{$ItemsPerPage}</per_page>";
		$xml_data .= "<folder>{$Folder}</folder>";
		if(!is_null($Notes)){$xml_data .= "<notes>{$Notes}</notes>";}
		$xml_data .= '</request>';
		
		return self::sendRequest($xml_data);
	}	

	public function invoice($id){
		if(isset($id)){
			$xml_data = '<?xml version="1.0" encoding="utf-8"?>';
			$xml_data .= '<request method="invoice.get">';
			$xml_data .= "<invoice_id>{$id}</invoice_id>";
			$xml_data .= '</request>';
			
			return self::sendRequest($xml_data);
		} else {
			return "No Invoice ID defined";
		}
	}
	
	public function deleteInvoice($id){
		if(isset($id)){
			$xml_data = '<?xml version="1.0" encoding="utf-8"?>';
			$xml_data .= '<request method="invoice.delete">';
			$xml_data .= "<invoice_id>{$id}</invoice_id>";
			$xml_data .= '</request>';
			
			$response = self::sendRequest($xml_data);
			return $response->response['status'];
		} else {
			return "No Invoice ID defined";
		}
	}
	
	public function setDomain($domain){
			self::$Domain = $domain;
	}
	public function setToken($token){
			self::$AuthToken = $token;
	}
	
	public function sendRequest($sendXML){
		if(self::$Domain != "" || self::$AuthToken != ""){
			$URL = "https://".self::$AuthToken.":x@".self::$Domain.".freshbooks.com/api/2.1/xml-in";
			$ch = curl_init($URL);
			//curl_setopt($ch, CURLOPT_MUTE, 1);
			curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
			curl_setopt($ch, CURLOPT_POST, 1);
			curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: text/xml'));
			curl_setopt($ch, CURLOPT_POSTFIELDS, "$sendXML");
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
			$output = curl_exec($ch);
			curl_close($ch);
 			
			//check output that connection is made succesfully
			//check for data.
			
			return simplexml_load_string($output);
		}
		else{
			return "Domain or token not set in _config.php";	
		}
	}

}
?>
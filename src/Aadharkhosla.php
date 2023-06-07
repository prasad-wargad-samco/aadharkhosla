<?php

namespace Samco\Aadharkhosla;
use Samco\Aadharkhosla\Models\ESignVendorsMaster;

class Aadharkhosla{
	/* Function to upload a file to KHOSLA LABS and get the response either SUCCESS/FAILS.
	 * If file successfully uploaded then redirecting it to the AADHARBRIDGE website for user to enter AADHAR ID
	 */
	private $APP_ENV, $KHOSLA_DOMAIN, $KHOSLA_SACODE, $KHOSLA_APIKEY;
	private $flag_vendor_details_found = true;
	public function __construct(){
		$esign_vendor_obj = new ESignVendorsMaster;
		$vendor_details = $esign_vendor_obj->getESignVendors(array('name' => 'KHOSLA', 'status' => 1));
		if($vendor_details->isEmpty()){
			$this->flag_vendor_details_found = false;
		}
		else{
			$vendor_details = $vendor_details[0];	// retrieving 1st record from available data
			// retrieving application environment, whether it's set to PRODUCTION or DEVELOPMENT.
			$this->APP_ENV = env('APP_ENV');
			if(strtolower($this->APP_ENV) == 'production'){
				// setting up PRODUCTION credentials
				$this->KHOSLA_DOMAIN = $vendor_details->live_domain_url;
				$this->KHOSLA_SACODE = $vendor_details->client_code;
				$this->KHOSLA_APIKEY = $vendor_details->live_api_key;
			}
			else{
				// setting up DEVELOPMENT credentials
				$this->KHOSLA_DOMAIN = $vendor_details->uat_domain_url;
				$this->KHOSLA_SACODE = $vendor_details->client_code;
				$this->KHOSLA_APIKEY = $vendor_details->uat_api_key;
			}
		}
		unset($esign_vendor_obj, $vendor_details);
	}

	/* Function to upload a document for signature and initiate signature process if upload is successful
	 */
	public function upload($input_arr = array()){
		/* Possible values for $input_arr are: array('requestId' => <Unique ID/Reference ID used for identifying the request>,
		 * 											 'doc_no' => <how many documents sent for esign>,
		 * 											 'doc_type' => <type of document sent for esign>,
		 *											 'reason' => <reason for doing esign>,
		 *											 'filename' => <filename will be seen to user>,
		 *											 'rectangle' => <X1, Y1, X2, Y2 coordinates used for placing a sign>,
		 * 											 'page_no' => <pages where sign needs to be taken>,
		 * 											 'file' => <actual file sent for doing a sign>,
		 * 											 'location' => <location needs to mentioned in signed document>,
		 *											 'signer_name' => <Name of document signer>);
		 */
		$output_arr = array('file_uploaded_id' => '', 'redirect_url' => '', 'api_response' => '');
		$err_flag = 0;                  // err_flag is 0 means no error
		$err_msg = array();             // err_msg stores list of errors found during execution
		extract($input_arr);

		// preparing hash using API KEY|<Unique ID/Reference ID>|Filename|Page No|Document Type
		$hash = $this->KHOSLA_APIKEY .'|'. $requestId .'|'. $filename .'|'. $page_no .'|'. $doc_type;
		$hash_code = hash("sha256", $hash);

		$url = $this->KHOSLA_DOMAIN .'/esign/uploadDocument';
		$data['client_code'] = $this->KHOSLA_SACODE;
		$data['client_request_id'] = $requestId;
		$data['doc_no'] = $doc_no;
		$data['doc_type'] = $doc_type;
		$data['reason'] = $reason;
		$data['filename'] = $filename;
		$data['rectangle'] = $rectangle;
		$data['page_no'] = $page_no;
		$data['hash'] = $hash_code;
		$data['file'] = $file;
		$data['location'] = $location;
		$data['signer_name'] = $signer_name;
		// y($data, 'data');

		/* Sample Response:
			stdClass Object
			(
				[code] => 000
				[message] => Uploaded Document
				[responseData] => 6103f513ac09fc0e529f642e
			)
		 */
		$result = get_content_by_curl($url, $data, array('ENC'=>'N'));
		$output_arr['api_response'] = $result;
		if(!empty($result) && json_decode($result) !== FALSE){
			$result = json_decode($result, true);		// parameter TRUE here gives data in an ARRAY format
			if(isset($result['code']) && ($result['code'] == '000') && isset($result['responseData']) && !empty($result['responseData'])){
				$output_arr['file_uploaded_id'] = $result['responseData'];

				// preparing hash using UPLOADED FILE ID and API KEY
				$hash = $output_arr['file_uploaded_id'] .'|'. $this->KHOSLA_APIKEY;
				$hash = hash("sha256", $hash);

				// redirecting to the KHOSLA website
				// as document uploaded successfully, redirecting user for doing an esign
				$output_arr['redirect_url'] = $this->KHOSLA_DOMAIN .'/esign/_initiateEsign?id='. $result['responseData'].'&h='.$hash;
			}
			else{
				// sending error details for further reference
				$err_flag = 1;
				$err_msg_text  = ((isset($result['code']) && !empty($result['code']))?$result['code']:'');
				if(!empty($err_msg_text)){
					$err_msg_text = 'Error Code: '. $err_msg_text .'. ';
				}
				$err_msg_text .= ((isset($result['message']) && !empty($result['message']))?$result['message']:'Unable to process your request');
				$err_msg[] = $err_msg_text;
				unset($err_msg_text);
			}
		}
		else{
			$err_flag = 1;
			$err_msg[] = 'Unable to upload document for esigning';
		}
		$output_arr['err_flag'] = $err_flag;
		$output_arr['err_msg'] = $err_msg;
		return $output_arr;
	}

	/* Function to download signed document
	 */
	public function download_signed_file($input_arr = array()){
		$output_arr = array('downloaded_file_name' => '', 'uploaded_file_name' => '', 'name_as_per_aadhar' => '', 'api_response' => '');
		$err_flag = 0;                  // err_flag is 0 means no error
		$err_msg = array();             // err_msg stores list of errors found during execution

		$hash = $input_arr['id'] .'|'. $this->KHOSLA_APIKEY;
		$hash = hash("sha256", $hash);
		$url = $this->KHOSLA_DOMAIN .'/esign/_downloadDocument';
		$data = json_encode(array('id' => $input_arr['id'], 'hash' => $hash));
		/*
			stdClass Object
			(
				[code] => 000
				[message] => Download Success
				[responseData] => stdClass Object
				(
					[fileContent] => <binary_file_data>
					[fileName] => <document_file_name>
					[nameAsPerAadhar] => <name specified on aadhar card>
				)
			)
		*/
		$result = get_content_by_curl($url, $data, array('Content-Type:application/json','Content-Length:'. strlen($data)));
		$output_arr['api_response'] = $result;
		if(!empty($result) && json_decode($result) !== FALSE){
			$result = json_decode($result, true);		// parameter TRUE here gives data in an ARRAY format
			if(isset($result['code']) && ($result['code'] == '000') && isset($result['responseData']) && !empty($result['responseData'])){
				$output_arr['downloaded_file_name'] = str_replace('_esign_', '_signed_', $result['responseData']['fileName']);

				// checking file data received or not
				if(isset($result['responseData']['fileContent']) && !empty($result['responseData']['fileContent'])){
					file_put_contents(storage_path($input_arr['download_folder_path'] . $output_arr['downloaded_file_name']),base64_decode($result['responseData']['fileContent']));
				}
				// retrieving signed document file name
				if(isset($result['responseData']['fileName']) && !empty($result['responseData']['fileName'])){
					$output_arr['uploaded_file_name'] = $result['responseData']['fileName'];
				}
				// retrieving name as per aadhar card
				if(isset($result['responseData']['nameAsPerAadhar']) && !empty($result['responseData']['nameAsPerAadhar'])){
					$output_arr['name_as_per_aadhar'] = $result['responseData']['nameAsPerAadhar'];
				}
			}
			else{
				// sending error details for further reference
				$err_flag = 1;
				$err_msg_text = '';
				/*$err_msg_text  = ((isset($result['code']) && !empty($result['code']))?$result['code']:'');
				if(!empty($err_msg_text)){
					$err_msg_text = 'Error Code: '. $err_msg_text .'. ';
				}*/
				$err_msg_text .= ((isset($result['message']) && !empty($result['message']))?$result['message']:'Unable to proces your request');
				$err_msg[] = $err_msg_text;
				unset($err_msg_text);
			}
		}
		else{
			$err_flag = 1;
			$err_msg[] = 'Unable to download document for esigning';
		}
		$output_arr['err_flag'] = $err_flag;
		$output_arr['err_msg'] = $err_msg;
		return $output_arr;
	}
}

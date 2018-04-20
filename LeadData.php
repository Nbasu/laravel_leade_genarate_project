<?php

namespace App;

use DB;

use Illuminate\Database\Eloquent\Model;

class LeadData extends Model
{
    //
    function getLeadData(){
         $leadData = DB::select('select * from lead_data limit 0,1000');
         return $leadData;    
    }
    function getLeadDataColumns(){
         $leadData = DB::select("select column_name from information_schema.columns where TABLE_NAME = 'lead_data'");
         return $leadData;    
    }
    function getSubFiles($token = false){
         return DB::select("select * from lead_dta_sub_files ldsb join lead_dta_request ldr on ldr.request_id=ldsb.original_request_id where ldr.request_token = '$token'"); 
    }
    function deleteToken($token = false){
        $original_request_id = DB::select("select request_id from lead_dta_request where request_token = '$token'");
         DB::table('lead_dta_sub_files')->where('original_request_id', '=', $original_request_id[0]->request_id)->delete();
         return DB::table('lead_dta_request')->where('request_token', '=', $token)->delete();
    }
    function splitfiles(){
        $results = DB::table('lead_dta_request')->where('request_process_stats', '=', '1')->orderBy('request_id', 'asc')->get();
        $file_split_folder = APP_PATH;
        
        foreach ($results as $value) {
            $file_to_split = $file_split_folder . $value->request_file_location;
            $folder = time();
            $output_folder = 'files/output_splits/';
            $new_folder = $file_split_folder . $output_folder . $folder;
            
            if (mkdir($new_folder,0777)) {
//                echo 'sh '.$_SERVER['DOCUMENT_ROOT'] . '/arleadsystem/split.sh ' . $file_split_folder . $value->request_file_location . ' ' . $new_folder . '/agent_' . time();die;
                exec('sh '.APP_PATH . '/split.sh ' . $file_split_folder . $value->request_file_location . ' ' . $new_folder . '/agent_' . time(), $output, $return);
//                echo $return;
                if ($return) {
                    echo "Error executing command!";
                    exit();
                } else {
                    $dir = $new_folder . '/';
                    // Open a directory, and read its contents
                    if (is_dir($dir)) {
                        if ($dh = opendir($dir)) {
                            while (($file = readdir($dh)) !== false) {
                                //Insert to database
                                if ($file != "." && $file != "..") {
                                    $data = Array("sub_file_name" => $file,
                                        "original_request_id" => $value->request_id,
                                        "sub_file_location" => $output_folder . $folder . '/' . $file,
                                        "sub_file_adddate" => date('Y-m-d H:i:s'),
                                        "sub_file_status" => '0',
                                    );
                                    if (DB::table('lead_dta_sub_files')->insert(
                                        $data
                                    )) {
                                        $data = Array(
                                            'request_process_stats' => '2'
                                        );
                                        DB::table('lead_dta_request')->where('request_id', '=', $value->request_id)->update($data);
                                    }
                                }
                            }
                            closedir($dh);
                        }
                    }
                }
            }
        }
        $results = DB::table('lead_dta_sub_files')->where('sub_file_status', '=', '0')->orderBy('sub_file_id', 'asc')->get();
        foreach ($results as $value) {
            $data = Array(
                'sub_file_status' => '1'
            );
            DB::table('lead_dta_sub_files')->where('sub_file_id', '=', $value->sub_file_id)->update($data);
            
            $path_to_file = $file_split_folder . $value->sub_file_location;
            $query = sprintf("LOAD DATA INFILE '%s' INTO TABLE lead_data 
            FIELDS TERMINATED BY ',' OPTIONALLY ENCLOSED BY '\"'
            LINES TERMINATED BY '\\n'
            IGNORE 1 LINES (NPN,FullName,FirstName,MiddleName,LastName,Suffix,Primary_Address1,Primary_Address2,Primary_City,Primary_State,Primary_ZipCode,Primary_ZipCode4,Primary_ZipCode3DigitSectional,Primary_MetropolitanArea,Primary_County,Primary_AddressType,Primary_AddressUpdate,Primary_USPSCertified,Primary_Phone,Primary_PhoneType,Primary_PhoneUpdate,Email_BusinessType,Email_BusinessTypeValidationSupported,Email_BusinessUpdate,Email_Business2Type,Email_Business2TypeValidationSupported,Email_Business2Update,FirmName,Number_YearsAnAgent,DateAddedToDiscoveryDatabase,BD_RIARep,RepCRD,BrokerDealerAffiliation,RIAAffiliation,SellsRetirementPlanProducts,EarliestAppointmentDate,AgentLicenseType_Health,Number_StateLicenses_Health,AgentLicenseType_Life,Number_StateLicenses_Life,AgentLicenseType_PropertyCasualty,Number_StateLicenses_PropertyCasualty,AgentLicenseType_VariableProducts,Number_StateLicenses_VariableProducts,DateOfBirth_Full,DateOfBirth_Year,Gender,Home_Address1,Home_Address2,Home_City,Home_State,Home_ZipCode,Home_ZipCode4,Home_ZipCode3DigitSectional,Home_MetropolitanArea,Home_County,Home_AddressUpdate,Home_USPSCertified,Home_Phone,Email_PersonalType,Email_PersonalTypeValidationSupported,Email_PersonalUpdate)", addslashes($path_to_file));
//            die($query);
            $pdo = DB::connection()->getpdo();
            
               $d1=$pdo->exec($query);

            //if ($d1) {
                $data = Array(
                    'request_process_stats' => '3'
                );
                DB::table('lead_dta_request')->where('request_id', '=', $value->original_request_id)->update($data);
                
                $data = Array(
                    'sub_file_status' => '2',
                    'record_inserted' => $d1
                );
                DB::table('lead_dta_sub_files')->where('sub_file_id', '=', $value->sub_file_id)->update($data);
            //}else{
                
            //}//
        }
    }
//    function exportQuery($query){
//        $d1 = DB::connection()->getpdo()->exec($query);
//    }
    public function creategroup($data) {
        $createdate = time(); //$date->format('m-d-y H:i:s');
        DB::table('lead_groups')->insert($data);
        return true;
    }
    public function updategroup($data) {
        $createdate = time(); //$date->format('m-d-y H:i:s');
        DB::table('lead_groups')->where('id',$data['id'])->update($data);
        return true;
    }
    public function getgroup($group_id) {
        $groupData = DB::select('select * from lead_groups where id='.$group_id);
         return $groupData;    
    }
    function getGroupList() {
        return DB::table('lead_groups')->orderBy('id', 'desc')->get();
    }
}

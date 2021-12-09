<?php

namespace Samco\Aadharkhosla\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class ESignVendorsMaster extends Model
{
    /**
     * Author: Prasad Wargad
     * Purpose: Retrieving esign vendor details
     * Created: 11/08/2021
     * Modified:
     * Modified by:
     */
    public function getESignVendors($input_arr = array()){
        $where_conditions = array();
        if(is_array($input_arr) && count($input_arr) > 0){
            foreach($input_arr as $key => $value){
                if(in_array($key, array('print_query')) === FALSE){
                    $where_conditions[] = array($key, '=', $value);
                }
            }
            unset($key, $value);
        }

        $enable_query_log = false;
        if(isset($input_arr['print_query']) && (intval($input_arr['print_query']) == 1)){
            $enable_query_log = true;
            DB::enableQueryLog();
        }

        $esign_vendors = DB::table('esign_vendors_master')->select('esign_vendors_master.*')->where($where_conditions)->get();

        if($enable_query_log){
            $query = DB::getQueryLog();
            dd($query);
        }

        return $esign_vendors;
    }
}

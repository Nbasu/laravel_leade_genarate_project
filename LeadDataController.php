<?php

namespace App\Http\Controllers;
use App\LeadData;
use Request;
use Session;
use Redirect;
use Illuminate\Support\Facades\Input;
use App\Http\Requests;
use App\TemplateData;
use DB;
use App\Libraries\ssp;

class LeadDataController extends BaseController
{
    //
    private $leadData;
    public function __construct(){
        $this->leadData = new LeadData;
        if(!Session::get('isLoggedIn')){
            Redirect::to('/')->send();
        }
    }
    /**
     * Display a listing of the resource.
     *
     * @return Response
     */
    public function index()
    {
        
        $data = $this->leadData->getLeadData();
        $dataColumns = $this->leadData->getLeadDataColumns();

        if(isset($_GET['group_id'])){
            $group_data = $this->leadData->getgroup($_GET['group_id']);
            return view('pages.leadlist')->with('data',$data)->with('dataColumns',$dataColumns)->with('group_data',$group_data);
        }else{
            return view('pages.leadlist')->with('data',$data)->with('dataColumns',$dataColumns);
        }
        
    }
    
    public function serverProcessing()
    {
        // DB table to use
        $table = 'lead_data';

        // Table's primary key
        $primaryKey = 'NPN';

        // Array of database columns which should be read and sent back to DataTables.
        // The `db` parameter represents the column name in the database, while the `dt`
        // parameter represents the DataTables column identifier. In this case simple
        // indexes
        $i=0;
        $dataColumns = $this->leadData->getLeadDataColumns();
        $columns = array();
        foreach($dataColumns as $cols){
            $columns[] = array( 'db' => $cols->column_name, 'dt' => $i++ );
        }
        // SQL server connection information
        $sql_details = array(
            'user' => env('DB_USERNAME'),
            'pass' => env('DB_PASSWORD'),
            'db'   => env('DB_DATABASE'),
            'host' => env('DB_HOST')
        );


        /* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
        * If you just want to use the basic configuration for DataTables with PHP
        * server-side, there is no need to edit below this line.
        */

        
        $arr = SSP::simple( $_POST, $sql_details, $table, $primaryKey, $columns );
        echo json_encode($arr);
    }
    public function serverProcessingExport()
    {
        // DB table to use
        $table = 'lead_data';

        // Table's primary key
        $primaryKey = 'NPN';

        // Array of database columns which should be read and sent back to DataTables.
        // The `db` parameter represents the column name in the database, while the `dt`
        // parameter represents the DataTables column identifier. In this case simple
        // indexes
        $i=0;
        $dataColumns = $this->leadData->getLeadDataColumns();
        $columns = array();
        foreach($dataColumns as $cols){
            $columns[] = array( 'db' => $cols->column_name, 'dt' => $i++ );
        }
        // SQL server connection information
        $sql_details = array(
            'user' => env('DB_USERNAME'),
            'pass' => env('DB_PASSWORD'),
            'db'   => env('DB_DATABASE'),
            'host' => env('DB_HOST')
        );


        /* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
        * If you just want to use the basic configuration for DataTables with PHP
        * server-side, there is no need to edit below this line.
        */

        $query = SSP::simple( $_POST, $sql_details, $table, $primaryKey, $columns, true );
        echo env('APP_URL').$query;
        exit;
//        die($query);
//        $this->leadData->exportQuery($query);
//        echo json_encode($arr);
    }
    
    public function creategroup(){
        if(Request::isMethod('post')){
           $data = Input::all();
           $data['created'] = time();
           $data['updated'] = time();
           $data['creaded_by'] = Session::get('userId');
           $group  = $this->leadData->creategroup($data);
           if($group){
                $msg['type'] = "success";
                $msg['message']  = "Group Created Successfully.";
           }else{
               $msg['type'] = "error";
               $msg['message']  = "Group not Created.";
           }
           echo json_encode($msg);
        }
        exit;
    }
    public function updategroup(){
        if(Request::isMethod('post')){
           $data = Input::all();
           $data['updated'] = time();
           $group  = $this->leadData->updategroup($data);
           if($group){
                $msg['type'] = "success";
                $msg['message']  = "Group Updtaed Successfully.";
           }else{
               $msg['type'] = "error";
               $msg['message']  = "Group not Update.";
           }
           echo json_encode($msg);
        }
        exit;
    }
    public function viewgroups()
    {
        $data = $this->leadData->getGroupList();
        return view('pages.grouplist')->with('data',$data);
    }
}

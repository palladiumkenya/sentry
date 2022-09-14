<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class EmailController extends Controller
{
    public function Unsubscribe($email){
        
        config(['database.connections.sqlsrv.database' => 'PortalDev']);
        $contacts = DB::connection('sqlsrv')->table('DWHIdentity.dbo.OrganizationContactses')
            ->where('Unsubscribe', 0)
            ->where('Email', $email)
            ->update(array('Unsubscribe' => 1));
        return view('reports\partner\unsubscribe')->with('email', $email);
    }

    public function Resubscribe($email){
        
        config(['database.connections.sqlsrv.database' => 'PortalDev']);
        $contacts = DB::connection('sqlsrv')->table('DWHIdentity.dbo.OrganizationContactses')
            ->where('Unsubscribe', 1)
            ->where('Email', $email)
            ->update(array('Unsubscribe' => 0));
        return view('reports\partner\resubscribe')->with('email', $email);
    }
}

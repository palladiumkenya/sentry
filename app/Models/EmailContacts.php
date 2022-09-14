<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class EmailContacts extends Model
{
    use HasFactory, SoftDeletes;
    protected $table = 'test_mail_list';
    
}

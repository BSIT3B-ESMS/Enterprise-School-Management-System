<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class ContributionProfile extends Model {protected $guarded=[];protected function casts():array{return ['philhealth_enabled'=>'boolean','pagibig_enabled'=>'boolean','withholding_enabled'=>'boolean'];}}

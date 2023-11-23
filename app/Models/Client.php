<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Client extends Model
{
    use HasFactory;
    protected $table = "clients";
    protected $fillable = [
        'name',
        'email',
        'secondary_email',
        'additional_email',
        'phone',
        'birth_date',
        'address',
        'city',
        'status',
        'zip',
        'country',
        'projects',
        'company',
        'source',
        'skype',
        'last_worked'
        
    ];

    const STATE_ACTIVE = 0;
    const STATE_INACTIVE = 1;
    const STATE_TALKED = 2;

   public function projects()
   {
      return $this->hasMany(Project::class);
   }


    public static function getStatus($status) {
        switch($status) {
            case Client::STATE_ACTIVE:
                return "Active";
            break;
            case Client::STATE_INACTIVE:
                return "Inactive";
            break;
            case Client::STATE_TALKED:
                return "Talked";
            break;
            default:
                return "Not Defined";
        }
    }

    /*public static function getTalk($talk) {
        switch($talk) {
            case Client::TALK_YES:
                return "Yes";
            break;
            case Client::TALK_NO:
                return "No";
            break;
            default:
                return "Not Defined";
        }
    }*/

    public static function getProjectName($id) {
        $getName = Projects::where('id',$id)->first();

        if(!empty($getName)) {
            return $getName->project_name;
        }
    }

    public static function getCountry($id) {
        $getCountry = Country::where('id', $id)->first();
    
        if(!empty($getCountry)) {
            return $getCountry->name;
        }
    }
}

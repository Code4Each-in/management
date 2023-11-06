<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Projects extends Model
{
    use HasFactory;
    protected $fillable = [
        'project_name',
        'client_id',
        'live_url',
        'dev_url',
        'git_repo', 
        'tech_stacks',
        'start_date',   
        'end_date',
        'description',   
        'credentials',   
        'status',    
    ];
    public function projectAssigns()
    {
        return $this->hasMany(ProjectAssigns::class, 'project_id','id');
    }

    public function projectOnTicket()
    {
        return $this->hasMany(Ticket::class, 'project_id', 'id');
    }

    public function client()
    {
      return $this->belongsTo(Client::class);
    }
    

    public static function getClientName($id) {
        $getName = Client::where('id',$id)->first();

        if(!empty($getName)) {
            return $getName->client_name;
        }
    }
}

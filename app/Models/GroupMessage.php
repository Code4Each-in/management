<?php


namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GroupMessage extends Model
{
    protected $fillable = ['project_id', 'user_id', 'message', 'document'];
    protected $table = 'group_messages'; 

    public function user()
    {
        return $this->belongsTo(Users::class);
    }

    public function project()
    {
        return $this->belongsTo(Projects::class);
    }
    public function reads()
    {
        return $this->hasMany(GroupMessageRead::class, 'message_id');
    }

   
    
   

}

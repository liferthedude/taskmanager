<?php

namespace Lifer\TaskManager\Model;

use Illuminate\Database\Eloquent\Model;
use Lifer\TaskManager\Support\LoggingWithTags;

abstract class AbstractModel extends Model
{
    use LoggingWithTags;

    public function getID() {
        return $this->id;
    }

    public function getName() {
    	return $this->name;
    }

    public function getStatus() {
        return $this->status;
    }

    public function getCreatedAt() {
        return $this->created_at;
    }

}

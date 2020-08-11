<?php

namespace Lifer\TaskManager\Model;

use Illuminate\Database\Eloquent\Model;
use Lifer\TaskManager\Support\LoggingWithTags;
use Carbon\Carbon;

abstract class AbstractModel extends Model
{
    use LoggingWithTags;

    public function getID(): int {
        return $this->id;
    }

    public function getName(): string {
    	return $this->name;
    }

    public function getStatus(): string {
        return $this->status;
    }

    public function getCreatedAt(): Carbon {
        return $this->created_at;
    }

}

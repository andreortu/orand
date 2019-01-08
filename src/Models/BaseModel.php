<?php

namespace Orand\aocrudgenerator\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;

class BaseModel extends Model
{
    protected $model = '';
    protected $fields = [];
    protected $info_fillable = [];

    public function collection()
    {
        return $this->model->all($this->getColumns());
    }

    public function headings(): array
    {
        return $this->getColumns();
    }

    public function getColumns()
    {
        return Schema::getColumnListing($this->table);
    }

    public function getInfoFillable(): array
    {
        return $this->info_fillable;
    }

    public function getFields(): array
    {
        return $this->fields;
    }

}

<?php

namespace Orand\\aocrudgenerator\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class BaseController extends Controller
{
    protected $model = '';
    protected $has_relation = FALSE;
    protected $relation = [];
    protected $sort_hidden = [];

    public function getRows(Request $request)
    {
        $paginate = $request->paginate ?: 10;

        $id = (isset($request->id)) ? $request->id : 'id';
        $o = (isset($request->o)) ? $request->o : 'asc';
        if(in_array($request->id, array_keys($this->relation))) {
            $id = $this->relation[$request->id]['columns'];
        }

        if ($this->has_relation) {
            $data = $this->model->with(array_keys($this->relation))->orderBy($id, $o)->paginate($paginate);
        } else {
            $data = $this->model->orderBy($id, $o)->paginate($paginate);
        }

        return [
            'data' => $data,
            'relation' => $this->relation,
            'has_relation' => $this->has_relation,
            'sort_hidden' => $this->sort_hidden,
        ];
    }

    public function prepareStore($request)
    {
        $validator = Validator::make($request->all(),
            $this->model->getFields()
        );

        if($validator->fails()) {
            return [
                "fails" => true,
                "errors" => $validator->errors()
            ];
        } else {
            return [
                "fails" => false,
                "errors" => []
            ];
        }
    }

    public function store(Request $request)
    {
        $attach = false;
        try {
            $validator = $this->prepareStore($request);
            if(! $validator['fails']) {
                foreach ($request->all() as $key => $item) {
                    if(!in_array($key, array_keys($this->relation))) {
                        $this->model->{$key} = $item;
                    } else {
                        $attach = true;
                        $ids = collect($item)->map(function ($item) {
                            return $item['id'];
                        });
                    }
                    if(in_array('user_id', $this->model->getColumns())) {
                        $this->model->user_id = 2;
                    }
                }
                $this->model->save();
                if($attach) {
                    $this->attachItem($this->model, $ids->all());
                }

                return ["code" => 200, "message" => "Row Inserted!"];
            } else {
                return ["code" => 422, "errors" => $validator['errors']];
            }

        } catch (\Exception $e) {
            return ["code" => 500, "message" => $e->getMessage()];
        }
    }

    public function create()
    {
        return $this->model->getInfoFillable();
    }

    public function edit($id)
    {
        $fillable = $this->model->getFillable();
        $data =  ($this->model->find($id))->only($fillable);
        $info_fillable = $this->model->getInfoFillable();

        return ['model_row' => $data, 'row' => $info_fillable];
    }

    public function update($id, Request $request)
    {
        $attach = false;
        try {
            $validator = $this->prepareStore($request);
            if(! $validator['fails']) {
                $m = $this->model->find($id);
                foreach ($request->all() as $key => $item) {
                    if(!in_array($key, array_keys($this->relation))) {
                        $m->{$key} = $item;
                    } else {
                        $attach = true;
                        $ids = collect($item)->map(function ($item) {
                            return $item['id'];
                        });
                    }
                }

                $m->save();
                if($attach) {
                    $this->detachItem($m);
                    $this->attachItem($m, $ids->all());
                }

                return ["code" => 200, "message" => "Row Updated!"];
            } else {
                return ["code" => 422, "errors" => $validator['errors']];
            }
        } catch (\Exception $e) {
            return ["code" => 500, "message" => $e->getMessage()];

        }
    }

    public function show($id)
    {
        $fillable = $this->model->getFillable();
        return ($this->model->find($id));
    }

    public function search(Request $request)
    {
        $fillable = $this->model->getColumns();
        $search =  $request->search;
        $hidden = [];

        if($search == '') {
            return $this->model->with(array_keys($this->relation))->paginate(15);
        }

        if($this->has_relation) {
            foreach ( array_keys($this->relation) as $index => $item) {
                array_push($hidden, $item);
                $data = Product::with(array_keys($this->relation))->whereHas($item, function($q) use ($item, $search){
                    $q->where($this->relation[$item]['columns'], 'like', '%' . $search . '%');
                })->paginate(15);

                if (count($data->items())) {
                    return $data;
                }
            }
        }

        $data = $this->model->with(array_keys($this->relation))
            ->where(function ($query) use ($fillable, $search, $hidden) {
                foreach ($fillable as $index => $item) {
                    if(!in_array($item, $hidden)) {
                        $query->orWhere($item, 'like', '%'. $search .'%');
                    }
                }
            })->paginate(15);

        return $data;
    }

    public function destroy($id)
    {
        try {
            $this->model->where('id', $id)->delete();
            return ["code" => 200, "message" => "Row Deleted!"];
        } catch (\Exception $e) {
            return ["code" => 500, "message" => "Error! Row Not Deleted!"];
        }
    }

    public function deleteRows(Request $request)
    {
        try {
            $this->model->whereIn('id', $request->ids)->delete();
            return ["code" => 200, "message" => "Rows Deleted!"];
        } catch (\Exception $e) {
            return ["code" => 500, "message" => "Error! Rows Not Deleted!"];
        }
    }

    public function export()
    {
        return Excel::download($this->model, 'mock.xslx');
    }

    public function attachItem($m, $ids) {

    }
}

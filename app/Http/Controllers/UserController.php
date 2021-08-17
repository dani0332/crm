<?php

namespace App\Http\Controllers;

use App\Models\User;
use DataTables;
use DB;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:users-list|users-create|users-edit|users-delete', ['only' => ['index', 'store']]);
        $this->middleware('permission:users-create', ['only' => ['create', 'store']]);
        $this->middleware('permission:users-edit', ['only' => ['edit', 'update']]);
        $this->middleware('permission:users-delete', ['only' => ['destroy']]);
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $filteredData = [];
            $users = DB::select("SELECT u1.id
                                        ,u1.name
                                        ,u1.email
                                        ,u2.roles
                                        ,u1.created_at
                                        ,u1.updated_at
                                    FROM users u1
                                    JOIN (
                                        SELECT users.id
                                            ,GROUP_CONCAT(roles.name) AS roles
                                        FROM users
                                        INNER JOIN model_has_roles ON model_has_roles.model_id = users.id
                                        INNER JOIN roles ON roles.id = model_has_roles.role_id
                                        GROUP BY users.name, users.id
                                        ) u2 ON u2.id = u1.id");
            $filteredData = $users;
            if(!empty($request->email)){
                $collection = collect($filteredData);
                $filteredData = $collection->filter(function ($value, $key) use ($request) {
                    return $value->email == $request->email;
                });
            }
            if(!empty($request->name)){
                $collection = collect($filteredData);
                $filteredData = $collection->filter(function ($value, $key) use ($request) {
                    return strtoupper($value->name) == strtoupper($request->name);
                });
            }

            return Datatables::of($filteredData)
                ->addIndexColumn()
                ->make(true);
        }

        return view('user.view');
    }
    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $roles = Role::pluck('name', 'name')->all();
        return view('user.add', compact('roles'));
    }
    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $this->validate($request, [
            'name' => 'required|max:120',
            'email' => 'required|email|unique:users',
            'roles' => 'required',
            'password' => 'required',
        ]);

        $user = new User();
        $user->name = $request->name;
        $user->email = $request->email;
        $user->password = bcrypt($request->password);
        $user->save();
        $user->assignRole($request->input('roles'));
        if (isset($request->return_to_view))
            return redirect("admin/users/" . $user->id)->with('success', 'User has been stored');
    }
    /**
     * Display the specified resource.
     *
     * @param  \App\User  $user
     * @return \Illuminate\Http\Response
     */
    public function show(User $user)
    {
        return view('user.show', compact('user'));
    }
    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\User  $user
     * @return \Illuminate\Http\Response
     */
    public function edit(User $user)
    {
        $roles = Role::pluck('name', 'name')->all();
        $userRole = $user->roles->pluck('name', 'name')->all();
        return view('user.edit', compact('user', 'roles', 'userRole'));
    }
    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\User  $user
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, User $user)
    {
        $this->validate($request, [
            'name' => 'required|max:120',
            'roles' => 'required',
        ]);
        $user->name = $request->name;
        $user->email = $request->email;
        $user->password = bcrypt($request->password);
        $user->save();

        DB::table('model_has_roles')->where('model_id', $user->id)->delete();
        $user->assignRole($request->input('roles'));
        if (isset($request->return_to_view))
            return redirect("admin/users/" . $user->id)->with('success', 'User has been updated');
    }
    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\User  $user
     * @return \Illuminate\Http\Response
     */
    public function destroy(User $user)
    {
        $user->delete();
        return redirect()->route('users.index')->with('message', 'User has been deleted');
    }
}

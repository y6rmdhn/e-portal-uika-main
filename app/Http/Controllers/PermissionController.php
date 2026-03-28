<?php

namespace App\Http\Controllers;

use App\Models\Permission; 
use Illuminate\Http\Request;

class PermissionController extends Controller
{
    // Tampilkan semua permission
    public function index()
    {
        $permissions = Permission::all();
        return view('permissions.index', compact('permissions'));
    }

    // Form tambah permission
    public function create()
    {
        return view('permissions.create');
    }

    // Simpan permission baru
    public function store(Request $request)
    {
        $request->validate([
            'module' => 'required|string|max:100',
            'name'   => 'required|string|max:100',
            'description' => 'nullable|string|max:255',
        ]);

        Permission::create($request->all());
        return redirect()->route('permissions.index')->with('success', 'Permission berhasil ditambahkan.');
    }

    // Form edit permission
    public function edit(Permission $permission)
    {
        return view('permissions.edit', compact('permission'));
    }

    // Update permission
    public function update(Request $request, Permission $permission)
    {
        $request->validate([
            'module' => 'required|string|max:100',
            'name'   => 'required|string|max:100',
            'description' => 'nullable|string|max:255',
        ]);

        $permission->update($request->all());
        return redirect()->route('permissions.index')->with('success', 'Permission berhasil diperbarui.');
    }

    // Hapus permission
    public function destroy(Permission $permission)
    {
        $permission->delete();
        return redirect()->route('permissions.index')->with('success', 'Permission berhasil dihapus.');
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\LinkItems;
use Illuminate\Http\Request;
use DataTables;
use Illuminate\Support\Facades\Storage;

class LinkItemsController extends Controller
{

    public function __construct(){
        $this->middleware(['role:admin']);
    }

    public function index(Request $request)
    {
        if ($request->ajax()) {
            $searchQuery = $request->get('query');
            $items = LinkItems::select('*');
            
            if ($searchQuery) {
                $items = $items->where(function ($query) use ($searchQuery) {
                    $query->where('title', 'like', '%' . $searchQuery . '%');
                });
            }
    
            return DataTables::of($items)
                ->addIndexColumn()
                ->addColumn('action', function ($item) {
                    return '<span class="editItemBtn mx-3" data-id="' . $item->id . '">
                                <i class="cursor-pointer fas fa-edit text-secondary"></i>
                            </span>
                            <span class="deleteItemBtn" data-id="' . $item->id . '">
                                <i class="cursor-pointer fas fa-trash text-secondary"></i>
                            </span>';
                })
                ->addColumn('file_icon', function ($item) {
                    $iconUrl = $item->icon ? asset($item->icon) : asset('default/path/to/default-image.jpg');
                    return '<img src="' . $iconUrl . '" class="img-fluid me-3" style="max-width: 60px; height: auto;">';
                })
                ->rawColumns(['action', 'file_icon'])
                ->make(true);
        }
        return view('link-items.index');
    }


    public function createItem(Request $request)
    {
        try {
            $validatedData = $request->validate([
                'title' => 'required|unique:link_items,title|max:255',
                'link' => 'required|unique:link_items,link',
                'deskripsi' => 'required',
                'icon' => 'required|image|mimes:jpeg,png,jpg,gif,svg|max:2048', 
            ]);
    
            if ($request->hasFile('icon') && $request->file('icon')->isValid()) {
                $file = $request->file('icon');
                $filePath = $file->store('upload-icon', 'public');
                $url = Storage::url($filePath);
    
                $validatedData['icon'] = $url;
            }
    
            LinkItems::create($validatedData);
    
            return redirect()->back()->with('success', 'Item created successfully!');
        } catch (ValidationException $e) {
            return back()->withErrors($e->validator->errors())->withInput();
        } catch (\Exception $e) {
            return back()->with('error', 'An error occurred while creating the item.')->withInput();
        }
    }

    public function deleteItem(Request $request, $id)
    {
        $item = LinkItems::find($id);
        if (!$item) {
            return response()->json(['message' => 'Item not found'], 404);
        }

        if ($item->icon) {
            $filePath = str_replace('/storage/', '', $item->icon);
            Storage::disk('public')->delete($filePath);
        }

        $item->delete();

        return response()->json(['message' => 'Item deleted successfully'], 200);
    }


    public function showItem($id){
      $item = LinkItems::findOrFail($id);
      
      return response()->json($item);
    }

    public function updateItem(Request $request, $id)
    {
        $request->validate([
            'title' => 'required|string|max:255|unique:link_items,title,' . $id,
            'link' => 'required|string|max:255|unique:link_items,link,' . $id,
            'deskripsi' => 'required|string',
            'icon' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
        ], [
            'title.unique' => 'The title has already been taken.',
            'link.unique' => 'The link has already been taken.',
        ]);
    
        $item = LinkItems::findOrFail($id);
    
        if ($request->hasFile('icon')) {
            if ($item->icon) {
                $oldFilePath = str_replace('/storage/', '', $item->icon);
                Storage::disk('public')->delete($oldFilePath);
            }
    
            $file = $request->file('icon');
            $filePath = $file->store('upload-icon', 'public');
            $url = Storage::url($filePath);
    
            $item->icon = $url;
        }
    
        $item->title = $request->title;
        $item->link = $request->link;
        $item->deskripsi = $request->deskripsi;
    
        $item->save();
    
        return redirect()->back()->with('success', 'Item updated successfully!');
    }
    
}

<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Template;
use App\Models\Placeholder;
use App\Models\Doctor;

use App\Models\PlaceholderTemplate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class TemplateController extends Controller
{
    /**
     * Display a listing of the templates.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $templates = Template::with(['doctor', 'placeholders'])->get();
        return response()->json([
            'status' => 'success',
            'data' => $templates
        ]);
    }

    /**
     * Store a newly created template in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'content' => 'required|string',
            'doctor_id' => 'nullable|exists:doctors,id',
            'mime_type' => 'required|string',
            'placeholders' => 'nullable|array',
        ]);
        // get doctor name and doctor name get form users table a
        
        $doctornamne = Doctor::with(['user'])->findOrFail($validated['doctor_id']);
        $doctornamne = $doctornamne->user->name;
       
        $validated['file_path'] ='how are you';
        // i want to store the content 
        $content = $validated['content'];
        $validated['content'] = $content;

        // Remove placeholders from validated data
        $placeholders = $validated['placeholders'] ?? [];
        unset($validated['placeholders']);
        // Create the template
        $template = Template::create($validated);

        // Store placeholders if provided
        if (!empty($placeholders)) {
            foreach ($placeholders as $placeholderText) {
                // Extract placeholder info from format like {{type.field}}
                $placeholderText = trim($placeholderText);
                $content = str_replace(['{{', '}}'], '', $placeholderText);
                
                // Check if it's a custom placeholder or type.field format
                if (strpos($content, '.') !== false) {
                    list($type, $field) = explode('.', $content, 2);
                } else {
                    $type = 'custom';
                    $field = $content;
                }
                
                // Find the existing placeholder
                $placeholder = Placeholder::where('name', $placeholderText)->first();
                
                if ($placeholder) {
                    // Only create the pivot table entry
                    PlaceholderTemplate::create([
                        'template_id' => $template->id,
                        'placeholder_id' => $placeholder->id
                    ]);
                }
            }
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Template created successfully',
            'data' => $template->load('doctor', 'placeholders')
        ], 201);
    }

    /**
     * Display the specified template.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $template = Template::with(['doctor', 'placeholders'])->findOrFail($id);
        
        return response()->json([
            'status' => 'success',
            'data' => $template
        ]);
    }

    /**
     * Update the specified template in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $template = Template::findOrFail($id);

        $validated = $request->validate([
            'name' => 'string|max:255',
            'description' => 'nullable|string',
            'content' => 'string',
            'doctor_id' => 'nullable|exists:doctors,id',
            'mime_type' => 'string',
            'placeholders' => 'nullable|array',
        ]);

        // Handle file upload if present
        if ($request->hasFile('file')) {
            // Delete old file if exists
            if ($template->file_path) {
                Storage::disk('public')->delete($template->file_path);
            }
            
            $file = $request->file('file');
            $fileName = time() . '_' . Str::slug($request->name) . '.' . $file->getClientOriginalExtension();
            $filePath = $file->storeAs('templates', $fileName, 'public');
            $validated['file_path'] = $filePath;
        }

        // Remove placeholders from validated data
        $placeholders = $validated['placeholders'] ?? [];
        unset($validated['placeholders']);

        // Update the template
        $template->update($validated);

        // Update placeholders if provided
        if (isset($request->placeholders)) {
            // Delete existing placeholder associations
            PlaceholderTemplate::where('template_id', $template->id)->delete();
            
            // Create new placeholder associations
            foreach ($placeholders as $placeholderText) {
                // Extract placeholder info from format like {{type.field}}
                $placeholderText = trim($placeholderText);
                $content = str_replace(['{{', '}}'], '', $placeholderText);
                
                // Check if it's a custom placeholder or type.field format
                if (strpos($content, '.') !== false) {
                    list($type, $field) = explode('.', $content, 2);
                } else {
                    $type = 'custom';
                    $field = $content;
                }
                
                // Find or create the placeholder
                $placeholder = Placeholder::firstOrCreate(
                    ['value' => $placeholderText],
                    [
                        'name' => $field,
                        'type' => $type,
                        'field' => $field
                    ]
                );
                
                // Associate placeholder with template
                PlaceholderTemplate::create([
                    'template_id' => $template->id,
                    'placeholder_id' => $placeholder->id
                ]);
            }
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Template updated successfully',
            'data' => $template->fresh()->load('doctor', 'placeholders')
        ]);
    }

    /**
     * Remove the specified template from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $template = Template::findOrFail($id);
        
        // Delete file if exists
        if ($template->file_path) {
            Storage::disk('public')->delete($template->file_path);
        }
        
        // Delete placeholder associations
        PlaceholderTemplate::where('template_id', $template->id)->delete();
        
        // Delete the template
        $template->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Template deleted successfully'
        ]);
    }

    /**
     * Search for templates based on given parameters.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function search(Request $request)
    {
        $query = Template::query();

        if ($request->has('name')) {
            $query->where('name', 'like', '%' . $request->name . '%');
        }

        if ($request->has('doctor_id')) {
            $query->where('doctor_id', $request->doctor_id);
        }
        
        $templates = $query->with(['doctor', 'placeholders'])->get();

        return response()->json([
            'status' => 'success',
            'data' => $templates
        ]);
    }
}

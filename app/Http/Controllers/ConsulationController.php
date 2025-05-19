<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Template;
use App\Models\Placeholder;
use Barryvdh\DomPDF\Facade\Pdf;

class ConsulationController extends Controller
{
    public function GeneratePdf(Request $request)
    {
        try {
            // Validate the request
            $request->validate([
                'templateIds' => 'required|array',
                'templateIds.*' => 'exists:templates,id',
                'placeholderData' => 'required|array',
                'patientInfo' => 'required|array',
            ]);
            
            
            // Get the templates
            $templates = Template::whereIn('id', $request->templateIds)->get();
            
            if ($templates->isEmpty()) {
                return response()->json(['message' => 'No templates found'], 404);
            }

            // Combine all template content
            $htmlContent = '';
            foreach ($templates as $template) {
                $htmlContent .= $template->content . "\n";
            }

            foreach ($request->placeholderData as $key => $value) {
                // Handle both array and string values
                if (is_array($value)) {
                    $pattern = [];
                    $replacement = [];
                    foreach ($value as $val) {
                        $pattern[] = '/(\{\{\s*' . preg_quote($key, '/') . '\s*\}\}|' . preg_quote($key, '/') . ')/';
                        $replacement[] = $val;
                    }
                    $htmlContent = preg_replace($pattern, $replacement, $htmlContent);
                } else {
                    // Handle single string value
                    $htmlContent = preg_replace('/(\{\{\s*' . preg_quote($key, '/') . '\s*\}\}|' . preg_quote($key, '/') . ')/', $value, $htmlContent);
                }
            }

            // Generate PDF
            $pdf = PDF::loadHTML($htmlContent);
            
            // Set PDF options if needed
            $pdf->setPaper('a4', 'portrait');
            $pdf->setOptions([
                'isHtml5ParserEnabled' => true,
                'isRemoteEnabled' => true
            ]);

            // Return the PDF for download
            return $pdf->download('consultation_' . date('Y-m-d_H-i-s') . '.pdf');
        } catch (\Exception $e) {
            return response()->json(['message' => 'Failed to generate PDF: ' . $e->getMessage()], 500);
        }
    }
}

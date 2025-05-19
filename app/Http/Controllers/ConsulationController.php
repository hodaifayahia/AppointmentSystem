<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Template;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\Shared\Html;
use Illuminate\Support\Facades\Storage;
use App\Events\PdfGeneratedEvent;
use Barryvdh\DomPDF\Facade\Pdf;
use ZipArchive;

class ConsultationController extends Controller
{
    public function GenerateDocuments(Request $request)
    {
        try {
            // Validate the request
            $request->validate([
                'templateIds' => 'required|array',
                'templateIds.*' => 'exists:templates,id',
                'placeholderData' => 'required|array',
                'doctorId' => 'nullable',
                'patientId' => 'nullable',
                'appointmentId' => 'nullable',
                'format' => 'required|in:pdf,word,both'
            ]);
            
            // Set default values if not provided
            $request['doctorId'] = $request->doctorId ?? 3;
            $request['patientId'] = $request->patientId ?? 2;
            $request['appointmentId'] = $request->appointmentId ?? 1;
            
            // Get the templates
            $templates = Template::whereIn('id', $request->templateIds)->get();
            
            if ($templates->isEmpty()) {
                return response()->json(['message' => 'No templates found'], 404);
            }

            $generatedFiles = [];
            $processedContent = [];

            // Process templates and replace placeholders
            foreach ($templates as $template) {
                $content = $this->replacePlaceholders($template->content, $request->placeholderData);
                $processedContent[] = [
                    'template' => $template,
                    'content' => $content
                ];
            }

            // Generate files based on requested format
            switch ($request->format) {
                case 'pdf':
                    $generatedFiles = $this->generatePdfFiles($processedContent);
                    break;
                case 'word':
                    $generatedFiles = $this->generateWordFiles($processedContent);
                    break;
                case 'both':
                    $pdfFiles = $this->generatePdfFiles($processedContent);
                    $wordFiles = $this->generateWordFiles($processedContent);
                    $generatedFiles = array_merge($pdfFiles, $wordFiles);
                    break;
            }

            // Dispatch events for each generated file
            foreach ($generatedFiles as $file) {
                event(new PdfGeneratedEvent(
                    $request->templateIds,
                    $request->placeholderData,
                    $file['path'],
                    $request->doctorId,
                    $request->patientId,
                    $request->appointmentId
                ));
            }

            // Return response based on number of files
            if (count($generatedFiles) === 1) {
                // Single file - direct download
                return response()->download(storage_path('app/' . $generatedFiles[0]['path']))
                    ->deleteFileAfterSend(true);
            } else {
                // Multiple files - create and return zip
                return $this->createZipResponse($generatedFiles);
            }
            
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    private function replacePlaceholders($content, $placeholderData)
    {
        foreach ($placeholderData as $key => $value) {
            if (is_array($value)) {
                // For array values, join them or use the first value
                $replacementValue = is_array($value) ? implode(', ', $value) : $value;
            } else {
                $replacementValue = $value;
            }
            
            // Replace both {{key}} and key patterns
            $content = preg_replace(
                '/(\{\{\s*' . preg_quote($key, '/') . '\s*\}\}|(?<!\w)' . preg_quote($key, '/') . '(?!\w))/',
                $replacementValue,
                $content
            );
        }
        
        return $content;
    }

    private function generatePdfFiles($processedContent)
    {
        $pdfFiles = [];
        
        foreach ($processedContent as $item) {
            $template = $item['template'];
            $content = $item['content'];
            
            // Create PDF with proper HTML rendering
            $pdf = Pdf::loadHTML($content);
            $pdf->setPaper('A4', 'portrait');
            
            // Generate filename
            $filename = 'consultation_' . $template->id . '_' . date('Y-m-d_H-i-s') . '.pdf';
            $pdfPath = 'pdfs/' . $filename;
            
            // Save PDF
            Storage::put($pdfPath, $pdf->output());
            
            $pdfFiles[] = [
                'path' => $pdfPath,
                'filename' => $filename,
                'type' => 'pdf'
            ];
        }
        
        return $pdfFiles;
    }

    private function generateWordFiles($processedContent)
    {
        $wordFiles = [];
        
        foreach ($processedContent as $item) {
            $template = $item['template'];
            $content = $item['content'];
            
            // Create Word document
            $phpWord = new PhpWord();
            $section = $phpWord->addSection([
                'marginLeft' => 1440,
                'marginRight' => 1440,
                'marginTop' => 1440,
                'marginBottom' => 1440,
            ]);
            
            // Add HTML content to Word document
            try {
                // Use PhpWord's HTML parser to maintain formatting
                Html::addHtml($section, $content, false, false);
            } catch (\Exception $e) {
                // Fallback: Add as plain text if HTML parsing fails
                $plainText = strip_tags($content);
                $section->addText($plainText);
            }
            
            // Generate filename
            $filename = 'consultation_' . $template->id . '_' . date('Y-m-d_H-i-s') . '.docx';
            $wordPath = 'words/' . $filename;
            
            // Save Word document
            $tempFile = tempnam(sys_get_temp_dir(), 'phpword');
            $writer = IOFactory::createWriter($phpWord, 'Word2007');
            $writer->save($tempFile);
            
            Storage::put($wordPath, file_get_contents($tempFile));
            unlink($tempFile);
            
            $wordFiles[] = [
                'path' => $wordPath,
                'filename' => $filename,
                'type' => 'word'
            ];
        }
        
        return $wordFiles;
    }

    private function createZipResponse($files)
    {
        $zipFilename = 'consultation_documents_' . date('Y-m-d_H-i-s') . '.zip';
        $zipPath = 'temp/' . $zipFilename;
        $fullZipPath = storage_path('app/' . $zipPath);
        
        // Ensure temp directory exists
        Storage::makeDirectory('temp');
        
        $zip = new ZipArchive;
        if ($zip->open($fullZipPath, ZipArchive::CREATE) === TRUE) {
            foreach ($files as $file) {
                $filePath = storage_path('app/' . $file['path']);
                if (file_exists($filePath)) {
                    $zip->addFile($filePath, $file['filename']);
                }
            }
            $zip->close();
            
            // Clean up individual files
            foreach ($files as $file) {
                Storage::delete($file['path']);
            }
            
            return response()->download($fullZipPath, $zipFilename)
                ->deleteFileAfterSend(true);
        }
        
        return response()->json(['error' => 'Could not create zip file'], 500);
    }

    // Legacy method for backward compatibility
    public function GenerateWord(Request $request)
    {
        $request->merge(['format' => 'word']);
        return $this->GenerateDocuments($request);
    }

    // New method for PDF generation
    public function GeneratePdf(Request $request)
    {
        $request->merge(['format' => 'pdf']);
        return $this->GenerateDocuments($request);
    }
}
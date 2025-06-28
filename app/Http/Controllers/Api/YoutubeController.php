<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Koleksi;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

class YoutubeController extends Controller
{
    /**
     * Get YouTube embed data for a collection - Enhanced version
     */
    public function getYoutubeEmbed($id)
    {
        try {
            $koleksi = Koleksi::findOrFail($id);
        
            // Debug log
            Log::info("Getting YouTube embed for collection {$id}", [
                'youtube_link' => $koleksi->youtube_link,
                'has_link' => !empty($koleksi->youtube_link)
            ]);
        
            if (empty($koleksi->youtube_link)) {
                return response()->json([
                    'success' => false,
                    'message' => 'YouTube link tidak ditemukan untuk koleksi ini',
                    'debug' => [
                        'collection_id' => $id,
                        'youtube_link' => $koleksi->youtube_link,
                        'youtube_link_empty' => empty($koleksi->youtube_link)
                    ]
                ], 404);
            }

            // Extract YouTube video ID
            $embedId = $this->extractYoutubeId($koleksi->youtube_link);
        
            Log::info("YouTube ID extraction result", [
                'original_url' => $koleksi->youtube_link,
                'extracted_id' => $embedId
            ]);
        
            if (empty($embedId)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Format URL YouTube tidak valid',
                    'debug' => [
                        'original_url' => $koleksi->youtube_link,
                        'extracted_id' => $embedId
                    ]
                ], 400);
            }

            // Get video metadata
            $videoData = $this->getYoutubeVideoData($embedId);

            $response = [
                'success' => true,
                'data' => [
                    'embed_id' => $embedId,
                    'embed_url' => "https://www.youtube.com/embed/{$embedId}",
                    'watch_url' => $koleksi->youtube_link,
                    'video_data' => $videoData,
                    'koleksi' => [
                        'id' => $koleksi->id,
                        'judul' => $koleksi->judul,
                        'penulis' => $koleksi->penulis
                    ]
                ]
            ];

            Log::info("YouTube embed response", $response);
        
            return response()->json($response);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Koleksi tidak ditemukan',
                'error' => 'Collection not found'
            ], 404);
        } catch (\Exception $e) {
            Log::error("YouTube embed error", [
                'collection_id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan saat mengambil data YouTube',
                'error' => $e->getMessage(),
                'debug' => [
                    'collection_id' => $id,
                    'error_line' => $e->getLine(),
                    'error_file' => $e->getFile()
                ]
            ], 500);
        }
    }

    /**
     * Track YouTube video view
     */
    public function trackYoutubeView(Request $request, $id)
    {
        try {
            $koleksi = Koleksi::findOrFail($id);
            
            // Increment view count
            $koleksi->increment('views');

            return response()->json([
                'success' => true,
                'message' => 'View berhasil dicatat',
                'views' => $koleksi->views
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mencatat view'
            ], 500);
        }
    }

    /**
     * Get YouTube video thumbnail
     */
    public function getYoutubeThumbnail($id)
    {
        try {
            $koleksi = Koleksi::findOrFail($id);
            
            if (!$koleksi->youtube_link) {
                return response()->json([
                    'success' => false,
                    'message' => 'YouTube link tidak ditemukan'
                ], 404);
            }

            $embedId = $this->extractYoutubeId($koleksi->youtube_link);
            
            if (!$embedId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Format URL YouTube tidak valid'
                ], 400);
            }

            $thumbnails = [
                'default' => "https://img.youtube.com/vi/{$embedId}/default.jpg",
                'medium' => "https://img.youtube.com/vi/{$embedId}/mqdefault.jpg",
                'high' => "https://img.youtube.com/vi/{$embedId}/hqdefault.jpg",
                'standard' => "https://img.youtube.com/vi/{$embedId}/sddefault.jpg",
                'maxres' => "https://img.youtube.com/vi/{$embedId}/maxresdefault.jpg"
            ];

            return response()->json([
                'success' => true,
                'data' => [
                    'video_id' => $embedId,
                    'thumbnails' => $thumbnails
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil thumbnail'
            ], 500);
        }
    }

    /**
     * Extract YouTube video ID from URL - Enhanced version
     */
    private function extractYoutubeId($url)
    {
        if (empty($url)) {
            return null;
        }

        // Clean the URL
        $url = trim($url);
        
        // If it's already just an ID (11 characters, alphanumeric + underscore + dash)
        if (preg_match('/^[a-zA-Z0-9_-]{11}$/', $url)) {
            return $url;
        }

        // Various YouTube URL patterns
        $patterns = [
            // Standard watch URL
            '/(?:https?:\/\/)?(?:www\.)?youtube\.com\/watch\?v=([a-zA-Z0-9_-]{11})/',
            // Short URL
            '/(?:https?:\/\/)?youtu\.be\/([a-zA-Z0-9_-]{11})/',
            // Embed URL
            '/(?:https?:\/\/)?(?:www\.)?youtube\.com\/embed\/([a-zA-Z0-9_-]{11})/',
            // Mobile URL
            '/(?:https?:\/\/)?m\.youtube\.com\/watch\?v=([a-zA-Z0-9_-]{11})/',
            // YouTube URL with additional parameters
            '/(?:https?:\/\/)?(?:www\.)?youtube\.com\/watch\?.*v=([a-zA-Z0-9_-]{11})/',
            // Any YouTube domain with v parameter
            '/[?&]v=([a-zA-Z0-9_-]{11})/',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $url, $matches)) {
                return $matches[1];
            }
        }

        return null;
    }

    /**
     * Get basic video data (you can expand this to use YouTube API)
     */
    private function getYoutubeVideoData($videoId)
    {
        // Basic data without API call
        return [
            'video_id' => $videoId,
            'embed_url' => "https://www.youtube.com/embed/{$videoId}",
            'watch_url' => "https://www.youtube.com/watch?v={$videoId}",
            'thumbnail_url' => "https://img.youtube.com/vi/{$videoId}/hqdefault.jpg"
        ];
    }

    /**
     * Validate YouTube URL format
     */
    public function validateYoutubeUrl(Request $request)
    {
        $request->validate([
            'url' => 'required|url'
        ]);

        $url = $request->input('url');
        $videoId = $this->extractYoutubeId($url);

        if (!$videoId) {
            return response()->json([
                'success' => false,
                'message' => 'Format URL YouTube tidak valid'
            ], 400);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'is_valid' => true,
                'video_id' => $videoId,
                'embed_url' => "https://www.youtube.com/embed/{$videoId}",
                'thumbnail_url' => "https://img.youtube.com/vi/{$videoId}/hqdefault.jpg"
            ]
        ]);
    }

    /**
     * Test endpoint untuk debugging YouTube functionality
     */
    public function testYoutubeEndpoint()
    {
        return response()->json([
            'success' => true,
            'message' => 'YouTube Controller is working',
            'timestamp' => now(),
            'available_methods' => [
                'GET /api/youtube/test' => 'Test endpoint',
                'GET /api/youtube/embed/{id}' => 'Get embed data',
                'POST /api/youtube/validate-url' => 'Validate YouTube URL',
                'GET /api/youtube/thumbnail/{id}' => 'Get thumbnail',
                'POST /api/youtube/track-view/{id}' => 'Track view'
            ]
        ]);
    }

    /**
     * Debug specific collection YouTube data
     */
    public function debugCollection($id)
    {
        try {
            $koleksi = Koleksi::findOrFail($id);
            
            $debug_info = [
                'collection_id' => $koleksi->id,
                'title' => $koleksi->judul,
                'youtube_link' => $koleksi->youtube_link,
                'has_youtube_link' => !empty($koleksi->youtube_link),
                'youtube_link_length' => strlen($koleksi->youtube_link ?? ''),
            ];

            if ($koleksi->youtube_link) {
                $embedId = $this->extractYoutubeId($koleksi->youtube_link);
                $debug_info['extracted_id'] = $embedId;
                $debug_info['extraction_successful'] = !empty($embedId);
                
                if ($embedId) {
                    $debug_info['embed_url'] = "https://www.youtube.com/embed/{$embedId}";
                    $debug_info['watch_url'] = "https://www.youtube.com/watch?v={$embedId}";
                    $debug_info['thumbnail_url'] = "https://img.youtube.com/vi/{$embedId}/hqdefault.jpg";
                }
            }

            return response()->json([
                'success' => true,
                'debug_info' => $debug_info
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ], 500);
        }
    }

    /**
     * Test URL extraction with various YouTube URL formats
     */
    public function testUrlExtraction(Request $request)
    {
        $testUrls = [
            'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
            'https://youtu.be/dQw4w9WgXcQ',
            'https://www.youtube.com/embed/dQw4w9WgXcQ',
            'https://www.youtube.com/watch?v=dQw4w9WgXcQ&t=30s',
            'https://m.youtube.com/watch?v=dQw4w9WgXcQ',
            'dQw4w9WgXcQ' // Direct ID
        ];

        // Add custom URL if provided
        if ($request->has('url')) {
            $testUrls[] = $request->input('url');
        }

        $results = [];
        foreach ($testUrls as $url) {
            $extractedId = $this->extractYoutubeId($url);
            $results[] = [
                'original_url' => $url,
                'extracted_id' => $extractedId,
                'success' => !empty($extractedId),
                'embed_url' => $extractedId ? "https://www.youtube.com/embed/{$extractedId}" : null
            ];
        }

        return response()->json([
            'success' => true,
            'test_results' => $results
        ]);
    }
}
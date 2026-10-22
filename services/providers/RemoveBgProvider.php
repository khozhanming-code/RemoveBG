<?php
/**
 * Pixelcut background removal service implementation (multipart upload mode).
 */
class RemoveBgProvider {
    private string $apiKey;
    private string $apiUrl = 'https://api.developer.pixelcut.ai/v1/remove-background';

    public function __construct(string $apiKey) {
        $this->apiKey = $apiKey;
    }

    public function removeBackground(string $imagePath): array {
        if (empty($this->apiKey)) {
            return ['success' => false, 'error' => 'No valid AI provider API key is configured.'];
        }

        if (!file_exists($imagePath)) {
            return ['success' => false, 'error' => 'The temporary image file does not exist on the server.'];
        }

        // Read the file metadata for the multipart upload.
        $fileData = file_get_contents($imagePath);
        $fileName = basename($imagePath);
        $mimeType = mime_content_type($imagePath);

        // Send the image using cURL.
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->apiUrl);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'X-API-KEY: ' . $this->apiKey,
            'Accept: application/json'
        ]);
        
        // Build the multipart form with the image file.
        $cfile = new CURLFile($imagePath, $mimeType, $fileName);
        curl_setopt($ch, CURLOPT_POSTFIELDS, [
            'image' => $cfile,
            'format' => 'png'
        ]);
        
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($curlError) {
            return ['success' => false, 'error' => 'cURL connection error: ' . $curlError];
        }

        if ($httpCode !== 200) {
            $errorData = json_decode($response, true);
            $errorMsg = $errorData['message'] ?? $errorData['error'] ?? 'AI processing request failed (HTTP status: ' . $httpCode . ').';
            error_log(sprintf('Pixelcut API request failed: HTTP %d, response: %s', $httpCode, substr((string) $response, 0, 500)));
            return ['success' => false, 'error' => $errorMsg];
        }

        // Parse the JSON response.
        $data = json_decode($response, true);
        $resultUrl = $data['result_url'] ?? $data['url'] ?? null;
        if (is_string($resultUrl) && filter_var($resultUrl, FILTER_VALIDATE_URL)) {
            $imgBinary = $this->downloadResult($resultUrl);
            if ($imgBinary !== false) {
                return ['success' => true, 'data' => $imgBinary];
            }

            error_log('Pixelcut result download failed: ' . $resultUrl);
            return ['success' => false, 'error' => 'The AI returned a result URL, but the server could not download the processed image.'];
        }

        if (!empty($response) && str_starts_with($response, "\x89PNG")) {
            return ['success' => true, 'data' => $response];
        }

        error_log('Pixelcut response parsing failed: ' . substr((string) $response, 0, 500));
        return ['success' => false, 'error' => 'Failed to parse the AI provider response. Check the PHP error log.'];
    }

    private function downloadResult(string $url): string|false {
        $ch = curl_init($url);
        if ($ch === false) {
            return false;
        }

        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTPHEADER => ['Accept: image/png,image/*'],
        ]);

        $data = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($curlError || $httpCode < 200 || $httpCode >= 300 || !is_string($data) || $data === '') {
            return false;
        }

        return $data;
    }
}
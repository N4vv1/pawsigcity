<?php
/**
 * Supabase Configuration
 * 
 * IMPORTANT: Replace these values with your actual Supabase credentials
 * You can find these in your Supabase Dashboard:
 * 1. Go to https://app.supabase.com
 * 2. Select your project
 * 3. Go to Settings > API
 * 4. Copy the Project URL and anon/public key
 */

// Your Supabase Project URL (e.g., https://xxxxxxxxxxxxx.supabase.co)
define('SUPABASE_URL', 'https://pgapbbukmyitwuvfbgho.supabase.co');

// Your Supabase Anon/Public Key
define('SUPABASE_ANON_KEY', 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJpc3MiOiJzdXBhYmFzZSIsInJlZiI6InBnYXBiYnVrbXlpdHd1dmZiZ2hvIiwicm9sZSI6ImFub24iLCJpYXQiOjE3NTE3MjIxMTUsImV4cCI6MjA2NzI5ODExNX0.SYvqRiE7MeHzIcT4CnNbwqBPwiVKbO0dqqzbjwZzU8A');

// Storage bucket name (must exist in your Supabase Storage)
define('SUPABASE_BUCKET', 'gallery');

/**
 * Verify Supabase configuration is set
 */
function verifySupabaseConfig() {
    if (SUPABASE_URL === 'https://pgapbbukmyitwuvfbgho.supabase.co') {
        return [
            'configured' => false,
            'error' => 'Please update SUPABASE_URL in supabase_config.php'
        ];
    }
    
    if (SUPABASE_ANON_KEY === 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJpc3MiOiJzdXBhYmFzZSIsInJlZiI6InBnYXBiYnVrbXlpdHd1dmZiZ2hvIiwicm9sZSI6ImFub24iLCJpYXQiOjE3NTE3MjIxMTUsImV4cCI6MjA2NzI5ODExNX0.SYvqRiE7MeHzIcT4CnNbwqBPwiVKbO0dqqzbjwZzU8A') {
        return [
            'configured' => false,
            'error' => 'Please update SUPABASE_ANON_KEY in supabase_config.php'
        ];
    }
    
    return ['configured' => true];
}

/**
 * Get public URL for an image in Supabase Storage
 */
function getSupabaseImageUrl($filename) {
    return SUPABASE_URL . '/storage/v1/object/public/' . SUPABASE_BUCKET . '/' . $filename;
}
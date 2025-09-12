<?php
$conn = pg_connect("host=aws-0-us-east-2.pooler.supabase.com port=6543 dbname=postgres user=postgres.pgapbbukmyitwuvfbgho password=pawsigcity2025 sslmode=require");

if (!$conn) {
    die("Connection failed: " . pg_last_error());
}
?>
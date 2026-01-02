<?php
// Define your app store links
$android_link = "https://play.google.com/store/apps/details?id=com.ayka.patientapp";
$ios_link     = "https://apps.apple.com/in/app/ayka-care/id6744580942"; 
$default_link = "https://aykacare.in/"; // fallback (if neither Android nor iOS)

// Get the user agent
$user_agent = strtolower($_SERVER['HTTP_USER_AGENT']);

// Detect Android
if (strpos($user_agent, 'android') !== false) {
    header("Location: $android_link");
    exit();
}

// Detect iOS (iPhone, iPad, iPod)
if (strpos($user_agent, 'iphone') !== false || strpos($user_agent, 'ipad') !== false || strpos($user_agent, 'ipod') !== false) {
    header("Location: $ios_link");
    exit();
}

// Fallback (desktop or unknown device)
header("Location: $default_link");
exit();
?>

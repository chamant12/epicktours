<?php
/**
 * What the CMS can edit.
 *
 * $SETTINGS_FIELDS  – site-wide settings (Admin → Settings).
 * $PAGE_FIELDS      – editable text and images for each page (Content → Pages).
 * $IMAGE_SPECS      – the size every kind of uploaded image is resized to.
 *
 * Field types: text, textarea, image. Defaults are what the site shows until
 * someone saves a different value.
 */

/**
 * mode "cover" crops to exactly w×h (centre), "contain" fits inside w×h.
 * "small" is an extra smaller copy used for cards and thumbnails.
 */
$IMAGE_SPECS = [
    'logo'        => ['w' => 480,  'h' => 160,  'mode' => 'contain'],
    'hero'        => ['w' => 1920, 'h' => 1000, 'mode' => 'cover'],
    'side'        => ['w' => 1200, 'h' => 900,  'mode' => 'cover'],
    'tour'        => ['w' => 1600, 'h' => 800,  'mode' => 'cover', 'small' => [800, 500]],
    'destination' => ['w' => 900,  'h' => 700,  'mode' => 'cover', 'small' => [600, 460]],
];

$SETTINGS_FIELDS = [
    'General' => [
        'site_name'        => ['label' => 'Site name', 'type' => 'text', 'default' => 'EPICK Tours'],
        'site_tagline'     => ['label' => 'Tagline (under the logo)', 'type' => 'text', 'default' => 'Sri Lanka'],
        'logo'             => ['label' => 'Logo', 'type' => 'image', 'spec' => 'logo', 'default' => '', 'help' => 'Leave empty to use the built-in logo. Transparent PNG works best.'],
        'site_url'         => ['label' => 'Website address', 'type' => 'text', 'default' => '', 'help' => 'e.g. https://srilankahoteldeals.lk – used for links in emails. Leave empty to detect automatically.'],
    ],
    'SEO defaults' => [
        'meta_description' => ['label' => 'Default description', 'type' => 'textarea', 'counter' => 160, 'default' => 'Authentic journeys, curated experiences and trusted local expertise across Sri Lanka.', 'help' => 'Used on any page that has no description of its own.'],
        'meta_keywords'    => ['label' => 'Default keywords', 'type' => 'text', 'default' => 'sri lanka tours, sri lanka holidays, sri lanka travel, tour packages', 'help' => 'Comma separated. Used on any page that has no keywords of its own.'],
    ],
    'Contact details' => [
        'phone'   => ['label' => 'Phone', 'type' => 'text', 'default' => '+94 77 123 4567'],
        'email'   => ['label' => 'Email', 'type' => 'text', 'default' => 'info@epicktours.lk'],
        'address' => ['label' => 'Address', 'type' => 'text', 'default' => 'No. 25, Galle Road, Colombo 03, Sri Lanka'],
    ],
    'Tours & enquiries' => [
        'currency'         => ['label' => 'Currency shown on prices', 'type' => 'text', 'default' => 'USD'],
        'tour_categories'  => ['label' => 'Tour categories (one per line)', 'type' => 'textarea', 'default' => "Classic\nAdventure\nFamily\nHoneymoon\nLuxury"],
    ],
    'Contact form emails' => [
        'enquiry_email_to' => ['label' => 'Send new enquiries to', 'type' => 'text', 'default' => '', 'help' => 'One or more emails, separated by commas. Leave empty to send to every active administrator.'],
        'mail_from_email'  => ['label' => '"From" email address', 'type' => 'text', 'default' => '', 'help' => 'Use an address on your own domain (e.g. noreply@yourdomain.lk). Leave empty to use the contact email above.'],
        'mail_from_name'   => ['label' => '"From" name', 'type' => 'text', 'default' => '', 'help' => 'Leave empty to use the site name.'],
        'smtp_host'        => ['label' => 'SMTP server', 'type' => 'text', 'default' => '', 'help' => 'Recommended, e.g. mail.yourdomain.lk or smtp.gmail.com. Leave empty to use the server\'s built-in mail(), which often goes to spam.'],
        'smtp_port'        => ['label' => 'SMTP port', 'type' => 'text', 'default' => '587', 'help' => 'Usually 587 for TLS or 465 for SSL.'],
        'smtp_encryption'  => ['label' => 'SMTP encryption', 'type' => 'select', 'options' => ['tls' => 'TLS (port 587)', 'ssl' => 'SSL (port 465)', 'none' => 'None'], 'default' => 'tls'],
        'smtp_username'    => ['label' => 'SMTP username', 'type' => 'text', 'default' => ''],
        'smtp_password'    => ['label' => 'SMTP password', 'type' => 'password', 'default' => '', 'help' => 'Leave empty to keep the saved password.'],
    ],
];

/** SEO fields added to every page below (and to every tour). */
$SEO_FIELDS = [
    'meta_title'       => ['label' => 'Page title (browser tab & Google)', 'type' => 'text', 'counter' => 60, 'default' => '', 'help' => 'About 50–60 characters. Leave empty for the automatic title.'],
    'meta_description' => ['label' => 'Meta description', 'type' => 'textarea', 'rows' => 2, 'counter' => 160, 'default' => '', 'help' => 'The summary Google shows under the title, about 150–160 characters. Leave empty for the default from Settings.'],
    'meta_keywords'    => ['label' => 'Meta keywords', 'type' => 'text', 'default' => '', 'help' => 'Comma separated, e.g. sri lanka tours, wildlife safari, tea country. Leave empty for the defaults from Settings.'],
];

$PAGE_FIELDS = [
    'home' => [
        'title' => 'Home page',
        'url'   => 'index.php',
        'sections' => [
            'Hero' => [
                'hero_image'  => ['label' => 'Background image', 'type' => 'image', 'spec' => 'hero', 'default' => ''],
                'hero_line1'  => ['label' => 'Heading line 1', 'type' => 'text', 'default' => 'Discover'],
                'hero_line2'  => ['label' => 'Heading line 2', 'type' => 'text', 'default' => 'Sri Lanka'],
                'hero_lead'   => ['label' => 'Text under heading', 'type' => 'textarea', 'default' => "Authentic Journeys • Curated Experiences\nTrusted Local Expertise"],
                'hero_btn1'   => ['label' => 'Main button text', 'type' => 'text', 'default' => 'Explore Tours'],
                'hero_btn2'   => ['label' => 'Second button text', 'type' => 'text', 'default' => 'Plan a Custom Trip'],
            ],
            'Featured journeys' => [
                'featured_title' => ['label' => 'Section heading', 'type' => 'text', 'default' => 'Featured Journeys'],
                'featured_btn'   => ['label' => 'Button text', 'type' => 'text', 'default' => 'View all tours'],
            ],
            'Why travel with us' => [
                'why_title'  => ['label' => 'Section heading', 'type' => 'text', 'default' => 'Why travel with us'],
                'why1_icon'  => ['label' => 'Box 1 icon (Font Awesome)', 'type' => 'text', 'default' => 'fa-user-check'],
                'why1_title' => ['label' => 'Box 1 title', 'type' => 'text', 'default' => 'Local experts'],
                'why1_text'  => ['label' => 'Box 1 text', 'type' => 'textarea', 'default' => 'Sri Lankan guides who know every hidden corner.'],
                'why2_icon'  => ['label' => 'Box 2 icon', 'type' => 'text', 'default' => 'fa-route'],
                'why2_title' => ['label' => 'Box 2 title', 'type' => 'text', 'default' => 'Tailor-made'],
                'why2_text'  => ['label' => 'Box 2 text', 'type' => 'textarea', 'default' => 'Every itinerary can be adjusted to your pace and budget.'],
                'why3_icon'  => ['label' => 'Box 3 icon', 'type' => 'text', 'default' => 'fa-shield-heart'],
                'why3_title' => ['label' => 'Box 3 title', 'type' => 'text', 'default' => 'Travel with confidence'],
                'why3_text'  => ['label' => 'Box 3 text', 'type' => 'textarea', 'default' => '24/7 support from arrival to departure.'],
                'why4_icon'  => ['label' => 'Box 4 icon', 'type' => 'text', 'default' => 'fa-tags'],
                'why4_title' => ['label' => 'Box 4 title', 'type' => 'text', 'default' => 'Fair prices'],
                'why4_text'  => ['label' => 'Box 4 text', 'type' => 'textarea', 'default' => 'No hidden costs – what you see is what you pay.'],
            ],
        ],
    ],
    'tours' => [
        'title' => 'Tour packages page',
        'url'   => 'tours.php',
        'sections' => [
            'Header' => [
                'hero_title'    => ['label' => 'Heading', 'type' => 'text', 'default' => 'Tour Packages'],
                'hero_subtitle' => ['label' => 'Sub-heading', 'type' => 'textarea', 'default' => 'Handpicked itineraries to experience the best of Sri Lanka'],
                'empty_text'    => ['label' => 'Text when no tours match', 'type' => 'text', 'default' => 'No tours match this filter yet.'],
            ],
        ],
    ],
    'destinations' => [
        'title' => 'Destinations page',
        'url'   => 'destinations.php',
        'sections' => [
            'Header' => [
                'hero_title'    => ['label' => 'Heading', 'type' => 'text', 'default' => 'Explore Destinations'],
                'hero_subtitle' => ['label' => 'Sub-heading', 'type' => 'textarea', 'default' => "From ancient kingdoms to golden beaches,\ndiscover the many faces of Sri Lanka."],
            ],
        ],
    ],
    'experiences' => [
        'title' => 'Experiences page',
        'url'   => 'experiences.php',
        'sections' => [
            'Header' => [
                'hero_title'    => ['label' => 'Heading', 'type' => 'textarea', 'default' => "Unique\nExperiences"],
                'hero_subtitle' => ['label' => 'Sub-heading', 'type' => 'textarea', 'default' => 'Go beyond sightseeing with immersive, meaningful experiences across Sri Lanka.'],
                'hero_image'    => ['label' => 'Header image', 'type' => 'image', 'spec' => 'side', 'default' => ''],
            ],
        ],
    ],
    'about' => [
        'title' => 'About us page',
        'url'   => 'about.php',
        'sections' => [
            'Header' => [
                'hero_title'    => ['label' => 'Heading', 'type' => 'text', 'default' => 'About EPICK Tours'],
                'hero_subtitle' => ['label' => 'Sub-heading', 'type' => 'textarea', 'default' => 'A Sri Lankan travel company built on local knowledge and genuine hospitality.'],
            ],
            'Our story' => [
                'story_title' => ['label' => 'Heading', 'type' => 'text', 'default' => 'Our story'],
                'story_body'  => ['label' => 'Text (leave a blank line between paragraphs)', 'type' => 'textarea', 'rows' => 8, 'default' => "We are a team of Sri Lankan travel specialists who have spent years exploring every corner of the island – from the misty tea country to the wild national parks and the sun-drenched south coast.\n\nEvery tour we create is designed around you: your interests, your pace and your budget. Our experienced chauffeur guides look after you from the moment you land until you fly home."],
                'story_btn'   => ['label' => 'Button text', 'type' => 'text', 'default' => 'Plan your journey'],
                'story_image' => ['label' => 'Image', 'type' => 'image', 'spec' => 'side', 'default' => ''],
            ],
            'Numbers' => [
                'stat1_value' => ['label' => 'Number 1', 'type' => 'text', 'default' => '10+'],
                'stat1_label' => ['label' => 'Label 1', 'type' => 'text', 'default' => 'Years of experience'],
                'stat2_value' => ['label' => 'Number 2', 'type' => 'text', 'default' => '5,000+'],
                'stat2_label' => ['label' => 'Label 2', 'type' => 'text', 'default' => 'Happy travellers'],
                'stat3_value' => ['label' => 'Number 3', 'type' => 'text', 'default' => '50+'],
                'stat3_label' => ['label' => 'Label 3', 'type' => 'text', 'default' => 'Expert guides'],
                'stat4_value' => ['label' => 'Number 4', 'type' => 'text', 'default' => '24/7'],
                'stat4_label' => ['label' => 'Label 4', 'type' => 'text', 'default' => 'On-tour support'],
            ],
        ],
    ],
    'contact' => [
        'title' => 'Contact / custom tour page',
        'url'   => 'contact.php',
        'sections' => [
            'Intro' => [
                'title' => ['label' => 'Heading', 'type' => 'textarea', 'default' => "Plan Your\nSri Lanka Journey"],
                'intro' => ['label' => 'Intro text', 'type' => 'textarea', 'default' => 'Tell us your travel plans and our experts will create a personalised itinerary for you.'],
                'image' => ['label' => 'Image', 'type' => 'image', 'spec' => 'side', 'default' => ''],
            ],
            'Form' => [
                'form_title'      => ['label' => 'Form heading', 'type' => 'text', 'default' => 'Request a Custom Tour'],
                'form_button'     => ['label' => 'Button text', 'type' => 'text', 'default' => 'Send Enquiry'],
                'success_message' => ['label' => 'Thank-you message', 'type' => 'textarea', 'default' => 'Your enquiry has been received. One of our travel experts will reply within 24 hours.'],
                'countries'       => ['label' => 'Country list (one per line)', 'type' => 'textarea', 'rows' => 8, 'default' => "United Kingdom\nUnited States\nAustralia\nGermany\nFrance\nIndia\nNetherlands\nCanada\nChina\nJapan\nItaly\nSwitzerland\nSri Lanka\nOther"],
            ],
        ],
    ],
    'footer' => [
        'title' => 'Footer (all pages)',
        'url'   => 'index.php',
        'sections' => [
            'Footer' => [
                'about_text' => ['label' => 'About text', 'type' => 'textarea', 'default' => 'Authentic journeys, curated experiences and trusted local expertise across the island.'],
                'cta_title'  => ['label' => 'Call-to-action heading', 'type' => 'text', 'default' => 'Plan your trip'],
                'cta_text'   => ['label' => 'Call-to-action text', 'type' => 'textarea', 'default' => 'Tell us your dates and interests and we will design a tour just for you.'],
            ],
        ],
    ],
];

// Every page except the footer gets its own SEO section
foreach ($PAGE_FIELDS as $pageKey => $pageDef) {
    if ($pageKey !== 'footer') {
        $PAGE_FIELDS[$pageKey]['sections']['Search engine (SEO)'] = $SEO_FIELDS;
    }
}
unset($pageKey, $pageDef);

/** Find a field definition in $PAGE_FIELDS. */
function page_field(string $page, string $key): ?array
{
    global $PAGE_FIELDS;
    foreach ($PAGE_FIELDS[$page]['sections'] ?? [] as $fields) {
        if (isset($fields[$key])) {
            return $fields[$key];
        }
    }
    return null;
}

function setting_field(string $key): ?array
{
    global $SETTINGS_FIELDS;
    foreach ($SETTINGS_FIELDS as $fields) {
        if (isset($fields[$key])) {
            return $fields[$key];
        }
    }
    return null;
}

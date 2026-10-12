<?php
/**
 * Describes every table the admin panel can manage.
 * crud.php reads this file and builds the list, the form and the validation
 * automatically, so adding a new section only needs a new entry here.
 *
 * Field types: text, email, tel, url, number, money, date, textarea, select,
 *              bool, image, password, pivot (many-to-many checkboxes).
 * 'locked' => true shows a value without allowing it to be changed.
 * 'roles' limits a section to certain roles (default: admin and staff).
 */

$status   = ['type' => 'select', 'label' => 'Status', 'options' => ['active' => 'Active (visible)', 'hidden' => 'Hidden'], 'default' => 'active'];
$featured = ['type' => 'bool', 'label' => 'Featured on the home page'];
$county   = ['type' => 'select', 'label' => 'County', 'required' => true, 'options_sql' => 'SELECT id, name AS label FROM counties ORDER BY name'];
$richHint = 'Blank line = new paragraph. Start a line with "## " for a heading or "- " for a bullet point.';

return [
    'destinations' => [
        'title' => 'Destinations', 'singular' => 'destination', 'group' => 'Tourism', 'order' => 'name',
        'search' => ['name', 'summary', 'location'],
        'list'   => ['image' => 'Photo', 'name' => 'Name', 'county_id' => 'County', 'category' => 'Category', 'entry_fee' => 'Entry fee', 'is_featured' => 'Featured', 'status' => 'Status'],
        'fields' => [
            'name'        => ['type' => 'text', 'label' => 'Name', 'required' => true],
            'county_id'   => $county,
            'category'    => ['type' => 'select', 'label' => 'Category', 'required' => true, 'options' => ['Beach', 'Nature & Wildlife', 'Historical', 'Cultural', 'Waterfall']],
            'summary'     => ['type' => 'text', 'label' => 'Short summary', 'hint' => 'One sentence shown on cards (up to 255 characters).'],
            'description' => ['type' => 'textarea', 'label' => 'Full description', 'rows' => 9, 'hint' => $richHint],
            'location'    => ['type' => 'text', 'label' => 'Location'],
            'best_time'   => ['type' => 'text', 'label' => 'Best time to visit'],
            'entry_fee'   => ['type' => 'money', 'label' => 'Entry fee (USD)', 'default' => '0.00', 'hint' => 'Enter 0 for free entry.'],
            'image'       => ['type' => 'image', 'label' => 'Photo'],
            'is_featured' => $featured,
            'status'      => $status,
        ],
    ],

    'counties' => [
        'title' => 'Counties', 'singular' => 'county', 'group' => 'Tourism', 'order' => 'name',
        'search' => ['name', 'capital'],
        'list'   => ['name' => 'County', 'capital' => 'Capital', 'description' => 'Description'],
        'fields' => [
            'name'        => ['type' => 'text', 'label' => 'County name', 'required' => true],
            'capital'     => ['type' => 'text', 'label' => 'Capital city'],
            'description' => ['type' => 'textarea', 'label' => 'Description', 'rows' => 4],
            'image'       => ['type' => 'image', 'label' => 'Photo'],
        ],
    ],

    'hotels' => [
        'title' => 'Hotels', 'singular' => 'hotel', 'group' => 'Tourism', 'order' => 'name',
        'search' => ['name', 'address'],
        'list'   => ['image' => 'Photo', 'name' => 'Name', 'county_id' => 'County', 'price_per_night' => 'Per night', 'rooms_available' => 'Rooms', 'is_featured' => 'Featured', 'status' => 'Status'],
        'fields' => [
            'name'            => ['type' => 'text', 'label' => 'Hotel name', 'required' => true],
            'county_id'       => $county,
            'address'         => ['type' => 'text', 'label' => 'Address'],
            'description'     => ['type' => 'textarea', 'label' => 'Description', 'rows' => 6, 'hint' => $richHint],
            'amenities'       => ['type' => 'text', 'label' => 'Amenities', 'hint' => 'Separate with commas, e.g. Wi-Fi, Pool, Restaurant'],
            'stars'           => ['type' => 'select', 'label' => 'Star rating', 'options' => [1 => '1 star', 2 => '2 stars', 3 => '3 stars', 4 => '4 stars', 5 => '5 stars'], 'default' => 3],
            'price_per_night' => ['type' => 'money', 'label' => 'Price per night (USD)', 'required' => true],
            'rooms_available' => ['type' => 'number', 'label' => 'Rooms available', 'required' => true, 'default' => 10, 'min' => 0],
            'phone'           => ['type' => 'tel', 'label' => 'Phone'],
            'image'           => ['type' => 'image', 'label' => 'Photo'],
            'is_featured'     => $featured,
            'status'          => $status,
        ],
    ],

    'packages' => [
        'title' => 'Tour packages', 'singular' => 'tour package', 'group' => 'Tourism', 'order' => 'title',
        'search' => ['title', 'summary'],
        'list'   => ['image' => 'Photo', 'title' => 'Title', 'guide_id' => 'Guide', 'duration_days' => 'Days', 'price' => 'Price', 'is_featured' => 'Featured', 'status' => 'Status'],
        'fields' => [
            'title'         => ['type' => 'text', 'label' => 'Title', 'required' => true],
            'summary'       => ['type' => 'text', 'label' => 'Short summary'],
            'description'   => ['type' => 'textarea', 'label' => 'Description', 'rows' => 7, 'hint' => $richHint],
            'includes'      => ['type' => 'textarea', 'label' => 'What is included', 'rows' => 5, 'hint' => 'One item per line.'],
            'duration_days' => ['type' => 'number', 'label' => 'Duration (days)', 'required' => true, 'default' => 1, 'min' => 1],
            'price'         => ['type' => 'money', 'label' => 'Price per person (USD)', 'required' => true],
            'max_people'    => ['type' => 'number', 'label' => 'Maximum group size', 'required' => true, 'default' => 10, 'min' => 1],
            'guide_id'      => ['type' => 'select', 'label' => 'Assigned tour guide', 'nullable' => true, 'options_sql' => 'SELECT id, full_name AS label FROM guides ORDER BY full_name'],
            'destinations'  => ['type' => 'pivot', 'label' => 'Destinations visited', 'pivot_table' => 'package_destinations', 'self_key' => 'package_id', 'other_key' => 'destination_id',
                                'options_sql' => 'SELECT id, name AS label FROM destinations ORDER BY name'],
            'image'         => ['type' => 'image', 'label' => 'Photo'],
            'is_featured'   => $featured,
            'status'        => $status,
        ],
    ],

    'guides' => [
        'title' => 'Tour guides', 'singular' => 'tour guide', 'group' => 'Tourism', 'order' => 'full_name',
        'search' => ['full_name', 'languages'],
        'list'   => ['image' => 'Photo', 'full_name' => 'Name', 'phone' => 'Phone', 'languages' => 'Languages', 'experience_years' => 'Years', 'status' => 'Status'],
        'fields' => [
            'full_name'        => ['type' => 'text', 'label' => 'Full name', 'required' => true],
            'phone'            => ['type' => 'tel', 'label' => 'Phone'],
            'email'            => ['type' => 'email', 'label' => 'Email'],
            'languages'        => ['type' => 'text', 'label' => 'Languages spoken'],
            'experience_years' => ['type' => 'number', 'label' => 'Years of experience', 'default' => 1, 'min' => 0],
            'bio'              => ['type' => 'textarea', 'label' => 'Short biography', 'rows' => 4],
            'image'            => ['type' => 'image', 'label' => 'Photo'],
            'status'           => $status,
        ],
    ],

    'events' => [
        'title' => 'Events', 'singular' => 'event', 'group' => 'Tourism', 'order' => 'event_date DESC',
        'search' => ['title', 'location'],
        'list'   => ['title' => 'Event', 'location' => 'Location', 'event_date' => 'Date', 'status' => 'Status'],
        'fields' => [
            'title'       => ['type' => 'text', 'label' => 'Event title', 'required' => true],
            'county_id'   => ['type' => 'select', 'label' => 'County', 'nullable' => true, 'options_sql' => 'SELECT id, name AS label FROM counties ORDER BY name', 'hint' => 'Leave empty for nationwide events.'],
            'location'    => ['type' => 'text', 'label' => 'Location'],
            'event_date'  => ['type' => 'date', 'label' => 'Start date', 'required' => true],
            'end_date'    => ['type' => 'date', 'label' => 'End date (optional)'],
            'description' => ['type' => 'textarea', 'label' => 'Description', 'rows' => 4],
            'image'       => ['type' => 'image', 'label' => 'Photo'],
            'status'      => $status,
        ],
    ],

    'reviews' => [
        'title' => 'Reviews', 'singular' => 'review', 'group' => 'Visitors', 'order' => 'created_at DESC', 'can_create' => false,
        'search' => ['comment'],
        'list'   => ['user_id' => 'Tourist', 'destination_id' => 'Destination', 'rating' => 'Rating', 'comment' => 'Comment', 'status' => 'Status'],
        'fields' => [
            'user_id'        => ['type' => 'select', 'label' => 'Tourist', 'locked' => true, 'options_sql' => 'SELECT id, full_name AS label FROM users'],
            'destination_id' => ['type' => 'select', 'label' => 'Destination', 'locked' => true, 'options_sql' => 'SELECT id, name AS label FROM destinations'],
            'rating'         => ['type' => 'number', 'label' => 'Rating (1 to 5)', 'locked' => true],
            'comment'        => ['type' => 'textarea', 'label' => 'Comment', 'locked' => true],
            'status'         => ['type' => 'select', 'label' => 'Moderation', 'options' => ['pending' => 'Pending', 'approved' => 'Approved (shown on the site)', 'rejected' => 'Rejected'], 'hint' => 'Only approved reviews are visible to the public.'],
        ],
    ],

    'enquiries' => [
        'title' => 'Enquiries', 'singular' => 'enquiry', 'group' => 'Visitors', 'order' => 'created_at DESC', 'can_create' => false,
        'search' => ['name', 'email', 'subject'],
        'list'   => ['name' => 'From', 'email' => 'Email', 'subject' => 'Subject', 'created_at' => 'Received', 'status' => 'Status'],
        'fields' => [
            'name'    => ['type' => 'text', 'label' => 'From', 'locked' => true],
            'email'   => ['type' => 'email', 'label' => 'Email', 'locked' => true],
            'phone'   => ['type' => 'tel', 'label' => 'Phone', 'locked' => true],
            'subject' => ['type' => 'text', 'label' => 'Subject', 'locked' => true],
            'message' => ['type' => 'textarea', 'label' => 'Message', 'locked' => true],
            'reply'   => ['type' => 'textarea', 'label' => 'Reply / internal note', 'rows' => 5, 'hint' => 'Record the answer given to the visitor by phone or email.'],
            'status'  => ['type' => 'select', 'label' => 'Status', 'options' => ['new' => 'New', 'replied' => 'Replied']],
        ],
    ],

    'pages' => [
        'title' => 'Pages', 'singular' => 'page', 'group' => 'Site content', 'order' => 'title',
        'search' => ['title', 'slug'],
        'list'   => ['title' => 'Title', 'slug' => 'Link (page.php?slug=…)', 'status' => 'Status'],
        'fields' => [
            'title'   => ['type' => 'text', 'label' => 'Page title', 'required' => true],
            'slug'    => ['type' => 'text', 'label' => 'Slug', 'required' => true, 'pattern' => '[a-z0-9\-]+', 'hint' => 'Lowercase letters, numbers and hyphens only. The page opens at page.php?slug=your-slug'],
            'content' => ['type' => 'textarea', 'label' => 'Content', 'rows' => 16, 'hint' => $richHint],
            'status'  => $status,
        ],
    ],

    'menu_links' => [
        'title' => 'Menu links', 'singular' => 'menu link', 'group' => 'Site content', 'order' => 'location, sort_order', 'roles' => ['admin'],
        'search' => ['label', 'url'],
        'list'   => ['label' => 'Label', 'url' => 'Link', 'location' => 'Menu', 'sort_order' => 'Order', 'is_active' => 'Shown'],
        'fields' => [
            'label'      => ['type' => 'text', 'label' => 'Label', 'required' => true],
            'url'        => ['type' => 'text', 'label' => 'Link', 'required' => true, 'hint' => 'A page of this site (e.g. hotels.php or page.php?slug=about) or a full address starting with https://'],
            'location'   => ['type' => 'select', 'label' => 'Menu', 'options' => ['header' => 'Top navigation', 'explore' => 'Footer: Explore', 'footer' => 'Footer: Information']],
            'sort_order' => ['type' => 'number', 'label' => 'Position', 'default' => 10, 'hint' => 'Lower numbers appear first.'],
            'is_active'  => ['type' => 'bool', 'label' => 'Show this link', 'default' => 1],
        ],
    ],

    'users' => [
        'title' => 'User accounts', 'singular' => 'user account', 'group' => 'Administration', 'order' => 'role, full_name', 'roles' => ['admin'],
        'search' => ['full_name', 'email', 'phone'],
        'list'   => ['full_name' => 'Name', 'email' => 'Email', 'phone' => 'Phone', 'role' => 'Role', 'status' => 'Status', 'created_at' => 'Created'],
        'fields' => [
            'full_name' => ['type' => 'text', 'label' => 'Full name', 'required' => true],
            'email'     => ['type' => 'email', 'label' => 'Email address (used to sign in)', 'required' => true],
            'phone'     => ['type' => 'tel', 'label' => 'Phone'],
            'country'   => ['type' => 'text', 'label' => 'Country', 'default' => 'Liberia'],
            'role'      => ['type' => 'select', 'label' => 'Role', 'options' => ['tourist' => 'Tourist (books tours and hotels)', 'staff' => 'Staff (manages bookings and tourism content)', 'admin' => 'Administrator (full access)'],
                            'default' => 'tourist', 'hint' => 'The role decides what this person can do after signing in.'],
            'status'    => ['type' => 'select', 'label' => 'Account status', 'options' => ['active' => 'Active', 'suspended' => 'Suspended (cannot sign in)'], 'default' => 'active'],
            'password'  => ['type' => 'password', 'label' => 'Password', 'column' => 'password_hash', 'hint' => 'At least 8 characters. When editing, leave empty to keep the current password.'],
        ],
    ],
];

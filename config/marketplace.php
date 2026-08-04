<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Vendor listings while application is pending
    |--------------------------------------------------------------------------
    |
    | Vendors may list up to this many products before admin approval.
    | Set to null for no limit while pending.
    |
    */
    'max_listings_while_pending' => (int) env('VENDOR_MAX_LISTINGS_WHILE_PENDING', 2),

    'low_stock_threshold' => (int) env('VENDOR_LOW_STOCK_THRESHOLD', 5),

    // Max stock used for inventory progress bar visualization.
    'stock_display_cap' => (int) env('VENDOR_STOCK_DISPLAY_CAP', 50),

    'product_placeholder_image' => env(
        'PRODUCT_PLACEHOLDER_IMAGE',
        'https://images.unsplash.com/photo-1515488042361-ee00e0ddd4e4?auto=format&fit=crop&w=640&h=720&q=80'
    ),

    /*
    |--------------------------------------------------------------------------
    | Homepage category imagery
    |--------------------------------------------------------------------------
    |
    | Stock photos for category tiles. Override any category by adding
    | public/images/categories/{category_slug}.jpg (or .png / .webp).
    |
    */
    'homepage_category_images' => [
        'feeding_nursing' => 'https://images.unsplash.com/photo-1596464716127-f2a82984de30?auto=format&fit=crop&w=400&h=400&q=80',
        'nutrition' => 'https://images.unsplash.com/photo-1615485923737-f5e31e4f6c48?auto=format&fit=crop&w=400&h=400&q=80',
        'diapering_hygiene' => 'https://images.unsplash.com/photo-1584464491033-fe7d134bfbeb?auto=format&fit=crop&w=400&h=400&q=80',
        'sleep_nursery' => 'https://images.unsplash.com/photo-1522771739844-0743f2b1b0ac?auto=format&fit=crop&w=400&h=400&q=80',
        'baby_gear_transport' => 'https://images.unsplash.com/photo-1544361964021-70c5a346c122?auto=format&fit=crop&w=400&h=400&q=80',
        'clothing_footwear' => 'https://images.unsplash.com/photo-1503919548209-c8746f4cb697?auto=format&fit=crop&w=400&h=400&q=80',
        'health_safety' => 'https://images.unsplash.com/photo-1579684270323-817fdf3a61d6?auto=format&fit=crop&w=400&h=400&q=80',
        'toys_development' => 'https://images.unsplash.com/photo-1558060370-7e0cd823ed8c?auto=format&fit=crop&w=400&h=400&q=80',
        'electronics' => 'https://images.unsplash.com/photo-1585944150943-9486e8982d08?auto=format&fit=crop&w=400&h=400&q=80',
        'bath_potty' => 'https://images.unsplash.com/photo-1605000793929-288d0f586844?auto=format&fit=crop&w=400&h=400&q=80',
        'maternity_postnatal' => 'https://images.unsplash.com/photo-1555252333-9f8e92e65df9?auto=format&fit=crop&w=400&h=400&q=80',
    ],

    'shop_per_page' => (int) env('SHOP_PER_PAGE', 12),

    'stores_per_page' => (int) env('STORES_PER_PAGE', 24),

    // Days a sold-out product stays on the shop before being hidden.
    'sold_out_hidden_after_days' => (int) env('SOLD_OUT_HIDDEN_AFTER_DAYS', 10),

    // Mummish commission on vendor sales (basis points: 1000 = 10%).
    'vendor_commission_bps' => (int) env('VENDOR_COMMISSION_BPS', 1000),

    // Fallback when a region/city has no configured rate.
    'checkout_shipping_cents' => (int) env('CHECKOUT_SHIPPING_CENTS', 0),

    // Temporary testing switch — set SHIPPING_FREE=true to waive all delivery fees.
    'shipping_free' => filter_var(env('SHIPPING_FREE', false), FILTER_VALIDATE_BOOLEAN),

    /*
    |--------------------------------------------------------------------------
    | Checkout shipping by delivery location (amounts in pesewas / cents, GHS)
    |--------------------------------------------------------------------------
    |
    | Sourced from the Mckot courier price matrix (2026).
    |
    | Metro hubs (Accra / Kumasi / Takoradi) use mid within-zone rates from
    | each city's rate card. Other regions use the matrix's next-day intercity
    | rates from Accra (or Kumasi↔Sunyani for Bono). Cities named on the card
    | override via config/shipping_rates_by_city.json ("Region|City").
    |
    */
    'shipping_rates_by_region' => [
        // Accra within zone 5–10 km
        'Greater Accra' => (int) env('SHIPPING_GREATER_ACCRA_CENTS', 3500),
        // Kumasi metro area 5–10 km
        'Ashanti' => (int) env('SHIPPING_ASHANTI_CENTS', 3000),
        // Takoradi metro delivery 5–10 km
        'Western' => (int) env('SHIPPING_WESTERN_CENTS', 3000),
        // Accra ↔ Cape Coast next day
        'Central' => (int) env('SHIPPING_CENTRAL_CENTS', 5500),
        // Approx. Accra mid-range next day (not on card)
        'Eastern' => (int) env('SHIPPING_EASTERN_CENTS', 5500),
        // Accra ↔ Ho next day
        'Volta' => (int) env('SHIPPING_VOLTA_CENTS', 6000),
        // Long-haul estimate (not on card)
        'Northern' => (int) env('SHIPPING_NORTHERN_CENTS', 9000),
        'Upper East' => (int) env('SHIPPING_UPPER_EAST_CENTS', 10000),
        'Upper West' => (int) env('SHIPPING_UPPER_WEST_CENTS', 10000),
        // Kumasi ↔ Sunyani next day
        'Bono' => (int) env('SHIPPING_BONO_CENTS', 5500),
        'Bono East' => (int) env('SHIPPING_BONO_EAST_CENTS', 5500),
        'Ahafo' => (int) env('SHIPPING_AHAFO_CENTS', 5500),
        // Long-haul estimate (not on card)
        'Western North' => (int) env('SHIPPING_WESTERN_NORTH_CENTS', 8000),
        'Oti' => (int) env('SHIPPING_OTI_CENTS', 8000),
        'Savannah' => (int) env('SHIPPING_SAVANNAH_CENTS', 9000),
        'North East' => (int) env('SHIPPING_NORTH_EAST_CENTS', 10000),
    ],

    'shipping_rates_by_city' => is_readable(__DIR__.'/shipping_rates_by_city.json')
        ? json_decode((string) file_get_contents(__DIR__.'/shipping_rates_by_city.json'), true)
        : [],

    'ghana_regions' => [
        'Greater Accra',
        'Ashanti',
        'Western',
        'Central',
        'Eastern',
        'Volta',
        'Northern',
        'Upper East',
        'Upper West',
        'Bono',
        'Bono East',
        'Ahafo',
        'Western North',
        'Oti',
        'Savannah',
        'North East',
    ],

    'min_product_images' => (int) env('PRODUCT_MIN_IMAGES', 3),

    'max_product_images' => (int) env('PRODUCT_MAX_IMAGES', 8),

    'product_image_max_kb' => (int) env('PRODUCT_IMAGE_MAX_KB', 5120),

    'product_image_min_width' => (int) env('PRODUCT_IMAGE_MIN_WIDTH', 500),

    'product_image_min_height' => (int) env('PRODUCT_IMAGE_MIN_HEIGHT', 500),

    // Laplacian variance; higher = sharper. Keep low — phone compression often softens scores.
    'product_image_min_sharpness' => (float) env('PRODUCT_IMAGE_MIN_SHARPNESS', 20),

    'categories' => [
        'feeding_nursing' => 'Nursing',
        'nutrition' => 'Feeding & Nutrition ',
        'diapering_hygiene' => 'Diapering & Hygiene',
        'sleep_nursery' => 'Nursery & Decor',
        'baby_gear_transport' => 'Baby Gear & Transport',
        'clothing_footwear' => 'Clothing & Footwear',
        'health_safety' => 'Health & Safety',
        'toys_development' => 'Toys & Development',
        'electronics' => 'Electronics',
        'bath_potty' => 'Bath & Potty',
        'maternity_postnatal' => 'Maternity (Pre & Postnatal Care)',
    ],

    'categories_requiring_size' => ['clothing_footwear'],

    'health_services_professionals' => [
        [
            'slug' => 'dr-akosua-mensah',
            'name' => 'Dr. Akosua Mensah',
            'title' => 'Pediatrician',
            'specialty' => 'Pediatrics',
            'service' => 'Child Wellness Consultation',
            'rate' => 'GHS 250 / session',
            'rate_value' => 250,
            'visit_modes' => ['Virtual', 'In person'],
            'experience' => '10+ years in pediatric care',
            'location' => 'East Legon, Accra',
            'availability' => 'Mon - Fri, 9:00 AM - 4:00 PM',
            'next_available' => 'Today, 11:20 AM',
            'response_time' => '< 15 minutes',
            'languages' => ['English', 'Twi'],
            'about' => 'Specialized in infant growth monitoring, nutrition planning, and common childhood illness management.',
            'highlights' => [
                'Infant and toddler growth checks',
                'Vaccination guidance and plans',
                'Nutrition and feeding support',
            ],
            'slots' => [
                ['date' => 'Today', 'day' => 'Wed, 29 Jul', 'times' => ['09:20 AM', '09:40 AM', '10:00 AM', '11:20 AM']],
                ['date' => 'Tomorrow', 'day' => 'Thu, 30 Jul', 'times' => ['10:00 AM', '11:00 AM', '2:00 PM']],
                ['date' => 'Fri, 31 Jul', 'day' => 'Fri, 31 Jul', 'times' => ['09:00 AM', '10:20 AM', '3:00 PM']],
            ],
            'rate_card' => [
                ['service' => 'Initial consultation', 'price' => 'GHS 250', 'mode' => 'In person'],
                ['service' => 'Follow-up review',     'price' => 'GHS 180', 'mode' => 'In person'],
                ['service' => 'Virtual consultation', 'price' => 'GHS 150', 'mode' => 'Virtual'],
                ['service' => 'Virtual follow-up',    'price' => 'GHS 120', 'mode' => 'Virtual'],
            ],
            'booking_note' => 'Appointments are confirmed within 2 business hours after booking request.',
            'rating' => 4.8,
            'review_count' => 34,
            'image' => 'https://images.unsplash.com/photo-1559839734-2b71ea197ec2?auto=format&fit=crop&w=700&h=850&q=80',
        ],
        [
            'slug' => 'nurse-adjoa-opoku',
            'name' => 'Nurse Adjoa Opoku',
            'title' => 'Postnatal Care Nurse',
            'specialty' => 'Postnatal Care',
            'service' => 'Home Postnatal Support',
            'rate' => 'GHS 200 / visit',
            'rate_value' => 200,
            'visit_modes' => ['In person', 'Virtual'],
            'experience' => '8 years in maternal and newborn support',
            'location' => 'Spintex, Accra',
            'availability' => 'Daily, 8:00 AM - 6:00 PM',
            'next_available' => 'Today, 2:00 PM',
            'response_time' => '< 20 minutes',
            'languages' => ['English', 'Ga'],
            'about' => 'Provides mother-and-baby home care, breastfeeding support, and post-delivery recovery guidance.',
            'highlights' => [
                'Breastfeeding support sessions',
                'Baby bathing and hygiene coaching',
                'Mother recovery checks at home',
            ],
            'slots' => [
                ['date' => 'Today', 'day' => 'Wed, 29 Jul', 'times' => ['12:30 PM', '2:00 PM', '3:20 PM', '4:00 PM']],
                ['date' => 'Tomorrow', 'day' => 'Thu, 30 Jul', 'times' => ['8:30 AM', '10:00 AM', '1:00 PM', '3:00 PM']],
                ['date' => 'Fri, 31 Jul', 'day' => 'Fri, 31 Jul', 'times' => ['9:00 AM', '11:00 AM', '2:30 PM']],
            ],
            'rate_card' => [
                ['service' => 'Home postnatal visit',      'price' => 'GHS 200', 'mode' => 'In person'],
                ['service' => 'Newborn care orientation',  'price' => 'GHS 160', 'mode' => 'In person'],
                ['service' => 'Breastfeeding support',     'price' => 'GHS 170', 'mode' => 'Virtual'],
                ['service' => 'Virtual postnatal check-in','price' => 'GHS 130', 'mode' => 'Virtual'],
            ],
            'booking_note' => 'Same-day slots are available for bookings made before 11:00 AM.',
            'rating' => 4.9,
            'review_count' => 21,
            'image' => 'https://images.unsplash.com/photo-1584516150909-c43483ee7935?auto=format&fit=crop&w=700&h=850&q=80',
        ],
        [
            'slug' => 'dr-kwame-asare',
            'name' => 'Dr. Kwame Asare',
            'title' => 'Family Physician',
            'specialty' => 'Primary Care',
            'service' => 'Family Health Review',
            'rate' => 'GHS 220 / session',
            'rate_value' => 220,
            'visit_modes' => ['Virtual', 'In person'],
            'experience' => '12 years in family medicine',
            'location' => 'Airport Residential, Accra',
            'availability' => 'Mon - Sat, 10:00 AM - 7:00 PM',
            'next_available' => 'Tomorrow, 9:00 AM',
            'response_time' => '< 10 minutes',
            'languages' => ['English', 'Twi'],
            'about' => 'Supports holistic family wellness, preventive screening, and treatment plans for parents and children.',
            'highlights' => [
                'Preventive family health screening',
                'Chronic condition follow-up',
                'Care plans for children and parents',
            ],
            'slots' => [
                ['date' => 'Tomorrow', 'day' => 'Thu, 30 Jul', 'times' => ['09:00 AM', '09:20 AM', '10:20 AM', '11:00 AM']],
                ['date' => 'Fri, 31 Jul', 'day' => 'Fri, 31 Jul', 'times' => ['10:00 AM', '11:30 AM', '2:00 PM']],
                ['date' => 'Sat, 1 Aug', 'day' => 'Sat, 1 Aug', 'times' => ['9:30 AM', '10:30 AM', '12:00 PM']],
            ],
            'rate_card' => [
                ['service' => 'Family consultation',        'price' => 'GHS 220', 'mode' => 'In person'],
                ['service' => 'Preventive screening review','price' => 'GHS 260', 'mode' => 'In person'],
                ['service' => 'Telehealth check-in',        'price' => 'GHS 140', 'mode' => 'Virtual'],
                ['service' => 'Virtual family review',      'price' => 'GHS 160', 'mode' => 'Virtual'],
            ],
            'booking_note' => 'Evening slots are limited and fill up quickly.',
            'rating' => 4.7,
            'review_count' => 58,
            'image' => 'https://images.unsplash.com/photo-1612349317150-e413f6a5b16d?auto=format&fit=crop&w=700&h=850&q=80',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Brands per product category
    |--------------------------------------------------------------------------
    */
    'category_brands' => [
        'feeding_nursing' => [
            'Philips Avent',
            'Medela',
            'Tommee Tippee',
            "Dr. Brown's",
            'Chicco',
            'MAM',
            'Nuk',
            'Nuby',
            'Pigeon',
            'Mothercare',
            'BabyOno',
            'Suavinex',
            'Bambino Mio',
        ],
        'nutrition' => [
            'Nan',
            'SMA',
            'Aptamil',
            'Similac',
            'Cerelac',
            'Heinz Baby',
            'Cow & Gate',
            'Munchkin',
            'OXO Tot',
            'Béaba',
            'Skip Hop',
            'Vitamilk Baby',
            'Cerelac Ghana',
            'Happy Baby Organics',
        ],
        'diapering_hygiene' => [
            'Pampers',
            'Huggies',
            'Molfix',
            'Bambino',
            'Dada',
            'Drypers',
            "Johnson's Baby",
            'Sebamed',
            'Mustela',
            'Sudocrem',
            'Bepanthen',
            'Cetaphil Baby',
            'Aveeno',
            'Earth Mama',
            'Aquaphor',
            'CeraVe',
            'Softcare',
            'Bella Baby Happy',
            'Goon',
        ],
        'bath_potty' => [
            'Fisher-Price',
            'Skip Hop Moby',
            'Shnuggle',
            'Summer Infant',
            'BabyBjörn',
            'Tommee Tippee',
            'Baby Love',
            'Bambino Bath',
            'Mothercare Potty',
        ],
        'sleep_nursery' => [
            'Graco',
            'Chicco',
            'Baby Loft',
            'Mothercare',
            'Babymoon',
            'Motorola',
            'Vtech',
            'Philips Avent SCD',
            'Nanit',
            'BabySafe',
            'BabyTrend Ghana',
        ],
        'baby_gear_transport' => [
            'Chicco',
            'Graco',
            'Baby Jogger',
            'Joie',
            'Evenflo',
            'Maxi-Cosi',
            'Ergobaby',
            'BabyBjörn',
            'Tula',
            'Infantino',
            'BabyStar',
            'Little Angel',
            'Baby Love',
        ],
        'clothing_footwear' => [
            "Carter's",
            "OshKosh B'gosh",
            'H&M Baby',
            'Zara Baby',
            'Mothercare',
            'Next Baby',
            'Gap',
            'Lupilu',
            'George',
            'George Baby',
            'Primark Baby',
            'Woolworths Baby',
            'Ackermans',
            'Pep Stores',
            'Woodin Baby',
        ],
        'health_safety' => [
            'Safety 1st',
            'Munchkin',
            'Dreambaby',
            'BabyDan',
            'Braun Thermoscan',
            'Omron',
            'Vicks Baby',
            'BabySafe Ghana',
            'Chicco First Aid',
        ],
        'toys_development' => [
            'Fisher-Price',
            'VTech',
            'LeapFrog',
            'Melissa & Doug',
            'LEGO DUPLO',
            'Lamaze',
            'Bright Starts',
            'Baby Einstein',
            'Chicco',
            'Early Learning Centre',
            'Bambino Toys',
            'Ghana-made wooden toys',
        ],
        'electronics' => [
            'Philips Avent',
            'Tommee Tippee',
            'Chicco',
            'Braun',
            'Beurer',
        ],
        'maternity_postnatal' => [
            'Mothercare',
            'H&M Mama',
            'Seraphine',
            'PinkBlush',
            'Medela',
            'Lansinoh',
            'Medela Contact',
            'Earth Mama',
            'Pregnacare',
            'Elevit',
            "Nature's Plus Prenatal",
            'Belly Band Ghana',
        ],
    ],

    'clothing_sizes' => [
        'nb' => 'Newborn',
        '0_3m' => '0–3 months',
        '3_6m' => '3–6 months',
        '6_12m' => '6–12 months',
        '12_18m' => '12–18 months',
        '18_24m' => '18–24 months',
        '2t' => '2T',
        '3t' => '3T',
        '4t' => '4T',
        '5' => '5 years',
        '6' => '6 years',
        '7' => '7 years',
        '8' => '8 years',
    ],

    'product_material_tags' => [
        'organic_cotton' => 'Organic Cotton',
        'handmade' => 'Handmade',
        'recycled_materials' => 'Recycled Materials',
        'plastic_free' => 'Plastic-free',
        'fair_trade' => 'Fair Trade Certified',
    ],

    /*
    |--------------------------------------------------------------------------
    | Public contact
    |--------------------------------------------------------------------------
    */
    'support_email' => env('SUPPORT_EMAIL', 'info@themummish.com'),
    'support_phone' => env('SUPPORT_PHONE', '0208062428'),

    /*
    |--------------------------------------------------------------------------
    | Admin SMS notifications
    |--------------------------------------------------------------------------
    |
    | Comma-separated list of phone numbers to notify on new vendor registration.
    |
    */
    'admin_notification_phones' => array_values(array_filter(array_map(
        trim(...),
        explode(',', (string) env('ADMIN_NOTIFICATION_PHONE', '0208062428'))
    ))),

    /*
    |--------------------------------------------------------------------------
    | Vendor referral rewards
    |--------------------------------------------------------------------------
    |
    | Default payouts for referrers who bring vendors to the marketplace.
    | Per-referrer overrides can be set in the admin panel.
    |
    */
    'vendor_referral' => [
        'registration_reward_cents' => (int) env('VENDOR_REFERRAL_REGISTRATION_REWARD_CENTS', 50),
        'transaction_commission_bps' => (int) env('VENDOR_REFERRAL_COMMISSION_BPS', 200),
    ],

];

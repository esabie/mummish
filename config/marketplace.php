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

    'product_image_min_width' => (int) env('PRODUCT_IMAGE_MIN_WIDTH', 800),

    'product_image_min_height' => (int) env('PRODUCT_IMAGE_MIN_HEIGHT', 800),

    // Laplacian variance; higher = sharper. Typical phone product shots are well above 80.
    'product_image_min_sharpness' => (float) env('PRODUCT_IMAGE_MIN_SHARPNESS', 80),

    'product_image_min_brightness' => (int) env('PRODUCT_IMAGE_MIN_BRIGHTNESS', 25),

    'product_image_max_brightness' => (int) env('PRODUCT_IMAGE_MAX_BRIGHTNESS', 245),

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
            'Other',
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
            'Other',
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
            'Ghanaian maternity brands on Jumia/Koala',
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
